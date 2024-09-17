<?php
namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\EmailModele;
use Illuminate\Http\Request;
use App\Models\VariableEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmailModeleController extends Controller
{
    
    public function createEmailModele(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->commande_achat && !$sousUtilisateur->fonction_admin) {
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

        $validator = Validator::make($request->all(),[
            'type_modele' => 'required|in:facture,devi,resumer_vente,recu_paiement,relanceAvant_echeance,relanceApres_echeance,commande_vente,livraison,fournisseur',
            'object' => 'required|string|max:255',
            'contenu' => 'required|string',
            'fichiers.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048', // Gérer les fichiers multiples

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $emailModele = EmailModele::create([
            'type_modele' => $request->type_modele,
            'object' => $request->object,
            'contenu' => $request->contenu,
            'user_id' => $userId,
            'sousUtilisateur_id' => $sousUtilisateurId,
        ]);

       

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

        // Retourner la réponse
        return response()->json([
            'message' => 'Modèle d\'email créé avec succès.',
            'emailModele' => $emailModele,
        ], 201);
    }


    public function DetailEmailFacture_genererPDF($id_facture)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $invoice = Facture::find($id_facture);
        if (!$invoice) {
            return response()->json(['error' => 'Facture introuvable'], 404);
        }
    
        $modelEmail = EmailModele::where('type_modele', 'facture')
            ->where(function ($query) use ($sousUtilisateur_id, $user_id) {
                $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                    ->orWhere('user_id', $user_id);
            })
            ->first();
    
        if (!$modelEmail) {
            return response()->json(['error' => 'Modèle d\'email introuvable'], 404);
        }
    
        // Variables à remplacer dans le modèle
        $variables = [
            '{num_facture}' => $invoice->num_facture ?? 'N/A',
            '{date_facture}' => $invoice->date_creation ?? 'N/A',
            '{statut_paiement}' => $invoice->statut_paiement ?? '0.00',
            // Ajouter d'autres variables selon les besoins
        ];
    
        // Remplacer les variables dans le sujet et le contenu
        $subject = str_replace(array_keys($variables), array_values($variables), $modelEmail->object);
        $body = str_replace(array_keys($variables), array_values($variables), $modelEmail->contenu);
    
      // Charger les articles et autres relations nécessaires pour le PDF
    $invoice->load(['articles', 'echeances']);

    // Générer le PDF
    $pdf = Pdf::loadView('invoices.template', compact('invoice'));
    $pdfPath = storage_path('app/public/invoices/') . 'invoice_' . $invoice->id . '.pdf';
    $pdf->save($pdfPath);

    // Vérifier que le fichier a été stocké avec succès
    if (!file_exists($pdfPath)) {
        return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
    }
    
        // Retourner les détails du modèle d'email avec le PDF généré
        return response()->json([
            'subject' => $subject,
            'body' => $body,
            'pdf' => $pdfPath,
        ]);
    }

}
