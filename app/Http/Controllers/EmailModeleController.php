<?php
namespace App\Http\Controllers;

use App\Models\Devi;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Livraison;
use App\Models\BonCommande;
use App\Models\EmailModele;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\CommandeAchat;
use App\Models\VariableEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\PDFService;

class EmailModeleController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }
    
    public function createEmailModele(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'type_modele' => 'required|in:facture,devi,resumer_vente,recu_paiement,relanceAvant_echeance,relanceApres_echeance,commande_vente,livraison,fournisseur',
            'object' => 'required|string|max:255',
            'contenu' => 'required|string',
            'fichiers.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048', 
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $emailModele = EmailModele::where('type_modele', $request->type_modele)
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->first();
    
        if ($emailModele) {
            // Si un modèle existe, on le met à jour
            $emailModele->update([
                'object' => $request->object,
                'contenu' => $request->contenu,
            ]);
            Artisan::call(command: 'optimize:clear');

            // Supprimer les anciens fichiers joints
            $emailModele->attachments()->delete();
            Artisan::call(command: 'optimize:clear');

        } else {
            $emailModele = EmailModele::create([
                'type_modele' => $request->type_modele,
                'object' => $request->object,
                'contenu' => $request->contenu,
                'user_id' => $userId,
                'sousUtilisateur_id' => $sousUtilisateurId,
            ]);
            Artisan::call(command: 'optimize:clear');

        }
    
        // Gérer les fichiers joints
        if ($request->hasFile('fichiers')) {
            foreach ($request->file('fichiers') as $file) {
                // Enregistrer le fichier
                $path = $file->store('email_attachments', 'public');
    
                // Créer l'entrée de fichier joint
                $emailModele->attachments()->create([
                    'chemin_fichier' => $path,
                ]);
            }
        }
    
        // Charger les fichiers joints
        $emailModele->load('attachments');
    
        $message = $emailModele->wasRecentlyCreated ? 'Modèle d\'email créé avec succès.' : 'Modèle d\'email mis à jour avec succès.';
    
        return response()->json([
            'message' => $message,
            'emailModele' => $emailModele,
            'fichiers' => $emailModele->attachments, 
        ], 201);
    }

    public function DetailEmailFacture_genererPDF($id_facture, $modelDocumentId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $invoice = Facture::find($id_facture);
        if (!$invoice) {
            return ['error' => 'Facture introuvable'];
        }
        $modelEmail = Cache::remember('modelEmail', 3600, function () use ($sousUtilisateur_id, $user_id) {
      
         return EmailModele::where('type_modele', 'facture')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();

        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{VENTE_NUMERO}' => $invoice->num_facture ?? 'N/A',
            '{VENTE_DATE}' => $invoice->date_creation ?? 'N/A',
            '{DESTINATAIRE}' => $invoice->client->prenom_client.' '.$invoice->client->nom_client ?? 'N/A',
            '{VENTE_PRIX_TOTAL}' => $invoice->prix_TTC ?? 'N/A',
            '{ENTREPRISE}' => $invoice->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $invoice->articles->map(function ($articleFacture) {
                return [
                    'id' => $articleFacture->article->id,
                    'nom' => $articleFacture->article->nom_article,
                    'quantite' => $articleFacture->quantite_article,
                    'prix' => $articleFacture->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
      // Génération du PDF via le service
      $pdfPath = $this->pdfService->genererPDFFacture($id_facture, $modelDocumentId);

      if (!$pdfPath) {
          return ['error' => 'Erreur lors de la génération du fichier PDF'];
      }

      // Préparer les pièces jointes
      $attachments = [];
      foreach ($modelEmail->attachments as $attachment) {
          $attachments[] = asset('storage/' . $attachment->chemin_fichier);
      }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $invoice->client->email_client,
            'entreprise' => $invoice->user->email ,
            'nom_entreprise' => $invoice->user->nom_entreprise
        ];
    }

    public function envoyerEmailFacture($id_facture, $modelDocumentId)
    {
        $details = $this->DetailEmailFacture_genererPDF($id_facture, $modelDocumentId);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->to($details['client_email'])
                        ->from($details['entreprise'], $details['nom_entreprise'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body'])
                        ->attach($details['pdf']);

                        foreach ($details['attachments'] as $attachment) {
                            $message->attachFromStorage($attachment);
                        }
                    });
            
                    return response()->json(['message' => 'Email envoyé avec succès']);
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'envoi de l'email : {$e->getMessage()}");
                    return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
                }
    }

    public function DetailEmailDevi_genererPDF($id_devi, $modelDocumentId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $devi = Devi::find($id_devi);
        if (!$devi) {
            return ['error' => 'devi introuvable'];
        }
        $modelEmail = Cache::remember('model_email',3600, function () use ($sousUtilisateur_id, $user_id) {
        
        return EmailModele::where('type_modele', 'devi')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();

        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{DEVIS_NUMERO}' => $devi->num_devi ?? 'N/A',
            '{DEVIS_DATE}' => $devi->date_devi ?? 'N/A',
            '{DESTINATAIRE}' => $devi->client->prenom_client.' '.$devi->client->nom_client ?? 'N/A',
            '{DEVIS_PRIX_TOTAL}' => $devi->prix_TTC ?? 'N/A',
            '{ENTREPRISE}' => $devi->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $devi->articles->map(function ($articledevi) {
                return [
                    'id' => $articledevi->article->id,
                    'nom' => $articledevi->article->nom_article,
                    'quantite' => $articledevi->quantite_article,
                    'prix' => $articledevi->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $pdfPath = $this->pdfService->genererPDFDevis($id_devi, $modelDocumentId);

        if (!$pdfPath) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $devi->client->email_client,
            'entreprise' => $devi->user->email,
            'nom_entreprise' => $devi->user->nom_entreprise,
        ];
    }
    
    public function envoyerEmailDevi($id_devi, $modelDocumentId)
    {
        $details = $this->DetailEmailDevi_genererPDF($id_devi, $modelDocumentId);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailBonCommande_genererPDF($id_BonCommande)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $BonCommande = BonCommande::find($id_BonCommande);
        if (!$BonCommande) {
            return ['error' => 'BonCommande introuvable'];
        }
        $modelEmail = Cache::remember('model_email', 3600, function () use ($sousUtilisateur_id, $user_id) {
        return EmailModele::where('type_modele', 'commande_vente')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{COMMANDE_NUMERO}' => $BonCommande->num_commande ?? 'N/A',
            '{COMMANDE_DATE}' => $BonCommande->date_commande ?? 'N/A',
            '{DESTINATAIRE}' => $BonCommande->client->prenom_client.' '.$BonCommande->client->nom_client ?? 'N/A',
            '{COMMANDE_PRIX_TOTAL}' => $BonCommande->prix_TTC ?? 'N/A',
            '{ENTREPRISE}' => $BonCommande->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $BonCommande->articles->map(function ($articleBonCommande) {
                return [
                    'id' => $articleBonCommande->article->id,
                    'nom' => $articleBonCommande->article->nom_article,
                    'quantite' => $articleBonCommande->quantite_article,
                    'prix' => $articleBonCommande->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $BonCommande->load(['articles', 'echeances']);
    
        $pdf = Pdf::loadView('BonCommandes.template', compact('BonCommande'));
        $pdfPath = storage_path('app/public/BonCommandes/') . 'BonCommande_' . $BonCommande->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $BonCommande->client->email_client,
            'entreprise' => $BonCommande->user->email ,
            'nom_entreprise' => $BonCommande->user->nom_entreprise
        ];
    }

    public function envoyerEmailBonCommande($id_BonCommande)
    {
        $details = $this->DetailEmailBonCommande_genererPDF($id_BonCommande);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailLivraison_genererPDF($id_livraison)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $livraison = Livraison::find($id_livraison);
        if (!$livraison) {
            return ['error' => 'Livraison introuvable'];
        }
        $modelEmail = Cache::remember('model_email_Livraison', 3600, function () use ($sousUtilisateur_id, $user_id) {
       
        return EmailModele::where('type_modele', 'Livraison')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{LIVRAISON_NUMERO}' => $livraison->num_livraison ?? 'N/A',
            '{LIVRAISON_DATE}' => $livraison->date_livraison ?? 'N/A',
            '{DESTINATAIRE}' => $livraison->client->prenom_client.' '.$livraison->client->nom_client ?? 'N/A',
            '{LIVRAISON_PRIX_TOTAL}' => $livraison->prix_TTC ?? 'N/A',
            '{ENTREPRISE}' => $livraison->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $livraison->articles->map(function ($articleLivraison) {
                return [
                    'id' => $articleLivraison->article->id,
                    'nom' => $articleLivraison->article->nom_article,
                    'quantite' => $articleLivraison->quantite_article,
                    'prix' => $articleLivraison->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $livraison->load(['articles']);
    
        $pdf = Pdf::loadView('livraisons.template', compact('livraison'));
        $pdfPath = storage_path('app/public/livraisons/') . 'livraison_' . $livraison->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $livraison->client->email_client,
            'entreprise' => $livraison->user->email ,
            'nom_entreprise' => $livraison->user->nom_entreprise
        ];
    }

    public function envoyerEmailLivraison($id_livraison)
    {
        $details = $this->DetailEmailLivraison_genererPDF($id_livraison);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailCommandeAchat_genererPDF($id_CommandeAchat)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $CommandeAchat = CommandeAchat::find($id_CommandeAchat);
        if (!$CommandeAchat) {
            return ['error' => 'CommandeAchat introuvable'];
        }
        $modelEmail = Cache::remember('model_emailCommandeAchat', 3600, function () use ($sousUtilisateur_id, $user_id) {
       
        return EmailModele::where('type_modele', 'fournisseur')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{ACHAT_NUMERO}' => $CommandeAchat->num_commandeAchat ?? 'N/A',
            '{ACHAT_DATE}' => $CommandeAchat->date_commandeAchat ?? 'N/A',
            '{DESTINATAIRE}' => $CommandeAchat->fournisseur->prenom_fournisseur.' '.$CommandeAchat->fournisseur->nom_fournisseur ?? 'N/A',
            '{ACHAT_PRIX_TOTAL}' => $CommandeAchat->total_TTC ?? 'N/A',
            '{ENTREPRISE}' => $CommandeAchat->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $CommandeAchat->articles->map(function ($articleCommandeAchat) {
                return [
                    'id' => $articleCommandeAchat->article->id,
                    'nom' => $articleCommandeAchat->article->nom_article,
                    'quantite' => $articleCommandeAchat->quantite_article,
                    'prix' => $articleCommandeAchat->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $CommandeAchat->load(['articles']);
    
        $pdf = Pdf::loadView('CommandeAchats.template', compact('CommandeAchat'));
        $pdfPath = storage_path('app/public/CommandeAchats/') . 'CommandeAchat_' . $CommandeAchat->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'fournisseur_email' => $CommandeAchat->fournisseur->email_fournisseur,
            'entreprise' => $CommandeAchat->user->email ,
            'nom_entreprise' => $CommandeAchat->user->nom_entreprise
        ];
    }

    public function envoyerEmailCommandeAchat($id_CommandeAchat)
    {
        $details = $this->DetailEmailCommandeAchat_genererPDF($id_CommandeAchat);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
       // dd($details);
       
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['fournisseur_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    
    public function DetailEmailResumeVente_genererPDF($id_facture)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $invoice = Facture::find($id_facture);
        if (!$invoice) {
            return ['error' => 'Facture introuvable'];
        }
        $modelEmail = Cache::remember('modelEmailResumeVente', 3600, function () use ($sousUtilisateur_id, $user_id) {
        return EmailModele::where('type_modele', 'resumer_vente')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{VENTE_NUMERO}' => $invoice->num_facture ?? 'N/A',
            '{VENTE_DATE}' => $invoice->date_creation ?? 'N/A',
            '{DESTINATAIRE}' => $invoice->client->prenom_client.' '.$invoice->client->nom_client ?? 'N/A',
            '{VENTE_PRIX_TOTAL}' => $invoice->prix_TTC ?? 'N/A',
            '{ENTREPRISE}' => $invoice->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $invoice->articles->map(function ($articleFacture) {
                return [
                    'id' => $articleFacture->article->id,
                    'nom' => $articleFacture->article->nom_article,
                    'quantite' => $articleFacture->quantite_article,
                    'prix' => $articleFacture->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $invoice->load(['articles', 'echeances']);
    
        $pdf = Pdf::loadView('invoices.template', compact('invoice'));
        $pdfPath = storage_path('app/public/invoices/') . 'invoice_' . $invoice->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $invoice->client->email_client,
            'entreprise' => $invoice->user->email ,
            'nom_entreprise'=> $invoice->user->nom_entreprise
        ];
    }

    public function envoyerEmailResumeVente($id_facture)
    {
        $details = $this->DetailEmailResumeVente_genererPDF($id_facture);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailPaiementRecu_genererPDF($id_PaiementRecu)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $PaiementRecu = PaiementRecu::find($id_PaiementRecu);
        if (!$PaiementRecu) {
            return ['error' => 'PaiementRecu introuvable'];
        }
        $modelEmail = Cache::remember('model_email_paiement', 3600, function () use ($sousUtilisateur_id, $user_id) {
      
        return EmailModele::where('type_modele', 'recu_paiement')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{PAIEMENT_NUMERO}' => $PaiementRecu->num_paiement ?? 'N/A',
            '{PAIEMENT_DATE}' => $PaiementRecu->date_recu ?? 'N/A',
            '{DESTINATAIRE}' => $PaiementRecu->facture->client->prenom_client.' '.$PaiementRecu->facture->client->nom_client ?? 'N/A',
            '{PAYMENT_MONTANT}' => $PaiementRecu->montant ?? 'N/A',
            '{ENTREPRISE}' => $PaiementRecu->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $PaiementRecu->facture->articles->map(function ($articlePaiementRecu) {
                return [
                    'id' => $articlePaiementRecu->article->id,
                    'nom' => $articlePaiementRecu->article->nom_article,
                    'quantite' => $articlePaiementRecu->quantite_article,
                    'prix' => $articlePaiementRecu->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
    
        $pdf = Pdf::loadView('PaiementRecus.template', compact('PaiementRecu'));
        $pdfPath = storage_path('app/public/PaiementRecus/') . 'PaiementRecu_' . $PaiementRecu->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $PaiementRecu->facture->client->email_client,
            'entreprise' => $PaiementRecu->user->email ,
            'nom_entreprise' => $PaiementRecu->user->nom_entreprise,
        ];
    }

    public function envoyerEmailPaiementRecu($id_PaiementRecu)
    {
        $details = $this->DetailEmailPaiementRecu_genererPDF($id_PaiementRecu);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->from($details['entreprise'], $details['nom_entreprise']);
                $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailRelanceAvantEcheance_genererPDF($id_echeance)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $echeance = Echeance::find($id_echeance);
        if (!$echeance) {
            return ['error' => 'echeance introuvable'];
        }
        $modelEmail = Cache::remember('model_email_relanceAvant',3600, function () use ($sousUtilisateur_id, $user_id) {
      
        return EmailModele::where('type_modele', 'relanceAvant_echeance')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{VENTE_NUMERO}' => $echeance->facture->num_facture ?? 'N/A',
            '{VENTE_DATE}' => $echeance->facture->date_creation ?? 'N/A',
            '{DESTINATAIRE}' => $echeance->facture->client->prenom_client.' '.$echeance->facture->client->nom_client ?? 'N/A',
            '{ECHEANCE_MONTANT}' => $echeance->montant_echeance ?? 'N/A',
            '{ECHEANCE_DATE}' => $echeance->date_pay_echeance ?? 'N/A',
            '{ENTREPRISE}' => $echeance->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $echeance->facture->articles->map(function ($articleecheance) {
                return [
                    'id' => $articleecheance->article->id,
                    'nom' => $articleecheance->article->nom_article,
                    'quantite' => $articleecheance->quantite_article,
                    'prix' => $articleecheance->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $invoice = $echeance->facture;

        $invoice->load(['articles', 'echeances']);
    
        $pdf = Pdf::loadView('invoices.template', compact('invoice'));
        $pdfPath = storage_path('app/public/invoices/') . 'invoice_' . $invoice->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $echeance->facture->client->email_client,
            'entreprise' => $echeance->user->email ,
            'nom_entreprise' => $echeance->user->nom_entreprise
        ];
    }

    public function envoyerEmailRelanceAvantEcheance($id_echeance)
    {
        $details = $this->DetailEmailRelanceAvantEcheance_genererPDF($id_echeance);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                $message->to($details['client_email'])
                        ->from($details['entreprise'], $details['nom_entreprise'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }

    public function DetailEmailRelanceApresEcheance_genererPDF($id_echeance)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return ['error' => 'Accès non autorisé'];
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return ['error' => 'Vous n\'êtes pas connecté'];
        }
    
        $echeance = Echeance::find($id_echeance);
        if (!$echeance) {
            return ['error' => 'echeance introuvable'];
        }
        $modelEmail = Cache::remember('model_email_relanceApres',3600, function () use ($sousUtilisateur_id, $user_id) {
      
        return EmailModele::where('type_modele', 'relanceApres_echeance')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->with('attachments')
            ->first();
        });
        if (!$modelEmail) {
            return ['error' => 'Modèle d\'email introuvable'];
        }
        $variables = [
            '{VENTE_NUMERO}' => $echeance->facture->num_facture ?? 'N/A',
            '{VENTE_DATE}' => $echeance->facture->date_creation ?? 'N/A',
            '{DESTINATAIRE}' => $echeance->facture->client->prenom_client.' '.$echeance->facture->client->nom_client ?? 'N/A',
            '{ECHEANCE_MONTANT}' => $echeance->montant_echeance ?? 'N/A',
            '{ECHEANCE_DATE}' => $echeance->date_pay_echeance ?? 'N/A',
            '{ENTREPRISE}' => $echeance->user->nom_entreprise ?? 'N/A',
            '{LIST_PRODUCTS_SERVICES}' => $echeance->facture->articles->map(function ($articleecheance) {
                return [
                    'id' => $articleecheance->article->id,
                    'nom' => $articleecheance->article->nom_article,
                    'quantite' => $articleecheance->quantite_article,
                    'prix' => $articleecheance->prix_total_tva_article,
                ];
            }),
        ];
    
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
        $invoice = $echeance->facture;

        $invoice->load(['articles', 'echeances']);
    
        $pdf = Pdf::loadView('invoices.template', compact('invoice'));
        $pdfPath = storage_path('app/public/invoices/') . 'invoice_' . $invoice->id . '.pdf';
        $pdf->save($pdfPath);
    
        if (!file_exists($pdfPath)) {
            return ['error' => 'Erreur lors de la génération du fichier PDF'];
        }
    
        $attachments = [];
        if ($modelEmail->attachments) {
            foreach ($modelEmail->attachments as $attachment) {
                $attachments[] = asset('storage/' . $attachment->chemin_fichier);
            }
        }
    
        return [
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
            'attachments' => $attachments,
            'client_email' => $echeance->facture->client->email_client,
            'entreprise' => $echeance->user->email ,
            'nom_entreprise' => $echeance->user->nom_entreprise
        ];
    }

    public function envoyerEmailRelanceApresEcheance($id_echeance)
    {
        $details = $this->DetailEmailRelanceApresEcheance_genererPDF($id_echeance);
    
        if (isset($details['error'])) {
            return response()->json(['error' => $details['error']], 500);
        }
    
        // Vérifier si le PDF a été généré avec succès
        if (!file_exists($details['pdf'])) {
            return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
        }
    
        $emailData = [
            'subject' => $details['subject'],
            'body' => $details['body'],
            'pdfPath' => $details['pdf'],
            'attachments' => $details['attachments'],
        ];

      
    
        try {
            Mail::send([], [], function ($message) use ($emailData, $details) {
                        $message->from($details['entreprise'], $details['nom_entreprise']);
                        $message->to($details['client_email'])
                        ->subject($emailData['subject'])
                        ->html($emailData['body']);
    
                // Ajouter le PDF en pièce jointe
                if (file_exists($emailData['pdfPath'])) {
                    $message->attach($emailData['pdfPath']);
                }
    
                // Ajouter les autres pièces jointes
                foreach ($emailData['attachments'] as $attachment) {
                    $message->attachFromStorage($attachment);
                }
            });
    
            return response()->json(['message' => 'Email envoyé avec succès']);
    
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
    
            return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email'], 500);
        }
    }
}
