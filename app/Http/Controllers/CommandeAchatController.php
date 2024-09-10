<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Article;
use App\Models\Depense;
use App\Models\facture_Etiquette;
use App\Models\Historique;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CommandeAchat;
use App\Models\ArticleCommandeAchat;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CommandeAchatController extends Controller
{
    public function creerCommandeAchat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'num_commandeAchat'=>'nullable|string',
            'date_commandeAchat'=>'required|date',
            'date_livraison'=>'nullable|date',
            'total_TTC'=>'nullable|numeric',
            'titre'=>'nullable|string',
            'description'=>'nullable|string',
            'active_Stock'=> 'nullable|boolean',
            'statut_commande'=> 'nullable|in:commander,recu,annuler,brouillon',
            'fournisseur_id'=> 'nullable|exists:fournisseurs,id',
            'depense_id'=> 'nullable|exists:depenses,id',
            'commentaire'=> 'nullable|string',
            'note_interne' => 'nullable|string',
            'doc_interne' => 'nullable|string',
            'articles' => 'required|array',
            'articles.*.id_article' => 'required|exists:articles,id',
            'articles.*.quantite_article' => 'required|integer',
            'articles.*.prix_unitaire_article' => 'required|numeric',
            'articles.*.TVA_article' => 'nullable|numeric',
            'articles.*.reduction_article' => 'nullable|numeric',
            'articles.*.prix_total_article'=>'nullable|numeric',
            'articles.*.prix_total_tva_article'=>'nullable|numeric',

            'etiquettes' => 'nullable|array',
            'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
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
    
        $typeDocument = 'commande_achat';
        $numBonCommande = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $commandeData = [
            'num_commandeAchat' => $request->num_commandeAchat ?? $numBonCommande,
            'date_commandeAchat' => $request->date_commandeAchat,
            'date_livraison' => $request->date_livraison,
            'total_TTC' => $request->total_TTC,
            'titre' => $request->titre,
            'description' => $request->description,
            'active_Stock' => $request->active_Stock,
            'date_paiement' => $request->date_paiement,
            'statut_commande' => $request->statut_commande ?? 'commander',
            'fournisseur_id' => $request->fournisseur_id,
            'depense_id' => $request->depense_id,
            'commentaire' => $request->commentaire,
            'note_interne' => $request->note_interne,
            'doc_interne' => $request->doc_interne,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ];
    
        if ($request->depense_id) {
            $depense = Depense::find($request->depense_id);
            if ($depense && $depense->statut_depense == 'payer') {
                $commandeData['date_paiement'] = $depense->date_paiement_depense;
            }
        }
    
        $commande = CommandeAchat::create($commandeData);

        NumeroGeneratorService::incrementerCompteur($userId, 'commande_achat');

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des Commandes d\'achat ont été  creées',
            'id_commandeAchat' => $commande->id
        ]);

        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'commandeAchat_id' => $commande->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }
        // Ajouter les articles à la commande
        foreach ($request->articles as $articleData) {
            ArticleCommandeAchat::create([
                'id_CommandeAchat' => $commande->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $articleData['quantite_article'],
                'prix_unitaire_article' => $articleData['prix_unitaire_article'],
                'TVA_article' => $articleData['TVA_article'] ?? 0,
                'reduction_article' => $articleData['reduction_article'] ?? 0,
                'prix_total_article' => $articleData['prix_total_article'],
                'prix_total_tva_article' => $articleData['prix_total_tva_article'],
            ]);
        }

            if ($commande->active_Stock == 1) {
                foreach ($commande->articles as $article) {
                 $articleDb = Article::find($article->id_article);
                if ($articleDb && isset($articleDb->quantite) && isset($articleDb->quantite_alert)) {
                    // Créer une notification si la quantité atteint ou est inférieure à la quantité d'alerte
                    if ($articleDb->quantite <= $articleDb->quantite_alert) {
                        Notification::create([
                            'sousUtilisateur_id' => $sousUtilisateurId,
                            'user_id' => $userId,
                            'id_article' => $articleDb->id,
                            'message' => 'La quantité des produits (' . $articleDb->nom_article . ') atteint la quantité d\'alerte.',
                        ]);
                    }
                }

                }

            }

        
    
        return response()->json(['message' => 'Commande créée avec succès', 'commande' => $commande], 201);
    }
    
    public function listerToutesCommandesAchat()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
              return response()->json(['error' => 'Accès non autorisé'], 403);
              }
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $CommandeAchats = CommandeAchat::with('articles.article','Etiquettes.etiquette', 'fournisseur', 'depense')
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $CommandeAchats = CommandeAchat::with('articles.article','Etiquettes.etiquette', 'fournisseur', 'depense')
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $response = [];
        foreach ($CommandeAchats as $CommandeAchat) {
            $response[] = [
                'id' => $CommandeAchat->id,
                'num_CommandeAchat' => $CommandeAchat->num_commandeAchat,
                'date_creation' => $CommandeAchat->date_commandeAchat,
                'livraison'=> $CommandeAchat->date_livraison,
                'date_paiement' => $CommandeAchat->date_paiement,
                'prix_TTC' => $CommandeAchat->total_TTC,
                'Prenom_fournisseur' => $CommandeAchat->fournisseur->prenom_fournisseur ?? null, 
                'Nom_Fournisseur'=>$CommandeAchat->fournisseur->nom_fournisseur  ?? null,
                'note_interne' => $CommandeAchat->note_interne,
                'statut' => $CommandeAchat->statut_commande,

                'etiquettes' => $CommandeAchat->Etiquettes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
            ];
        }
       
    
        return response()->json(['CommandeAchats' => $response], 200);
    }

    public function afficherDetailCommandeAchat($id)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        // Rechercher la commande d'achat par son ID
        $commandeAchat = CommandeAchat::with(['fournisseur', 'articles.article','Etiquettes.etiquette', 'depense'])
            ->where('id', $id)
            ->first();
    
        // Vérifier si la commandeAchat existe
        if (!$commandeAchat) {
            return response()->json(['error' => 'Commande d\'achat non trouvée'], 404);
        }
    
        // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
        $dateCreation = Carbon::parse($commandeAchat->date_commandeAchat);
    
        // Préparer la réponse
        $response = [
            'id_commandeAchat' => $commandeAchat->id,
            'numero_commandeAchat' => $commandeAchat->num_commandeAchat,
            'titre' => $commandeAchat->titre,
            'date_commandeAchat' => $dateCreation->format('Y-m-d H:i:s'),
            'date_livraison' => $commandeAchat->date_livraison,
            'date_paiement' => $commandeAchat->date_paiement,
            'fournisseur' => $commandeAchat->fournisseur ? [
                'id_fournisseur' => $commandeAchat->fournisseur->id,
                'nom' => $commandeAchat->fournisseur->nom_fournisseur,
                'prenom' => $commandeAchat->fournisseur->prenom_fournisseur,
                'adresse' => $commandeAchat->fournisseur->adress_fournisseur,
                'telephone' => $commandeAchat->fournisseur->tel_fournisseur,
                'nom_entreprise' => $commandeAchat->fournisseur->nom_entreprise,
            ] : null,
            'depense' => $commandeAchat->depense ? [
                'id_depense' => $commandeAchat->depense->id,
                'num_depense' => $commandeAchat->depense->num_depense,
                'date_paiement' => $commandeAchat->depense->date_paiement,
                'statut_depense' => $commandeAchat->depense->statut_depense,
            ] : null,
            'note_commandeAchat' => $commandeAchat->note_interne,
            'active_Stock' => $commandeAchat->active_Stock,
            'prix_TTC' => $commandeAchat->prix_TTC,
            'commentaire' => $commandeAchat->commentaire,
            'statut_commandeAchat' => $commandeAchat->statut_commande,
            'doc_interne' => $commandeAchat->doc_interne ?? null,
            'articles' => [],

            'etiquettes' => $commandeAchat->Etiquettes->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all(),
        ];
    
        // Vérifier si 'articles' est non nul et une collection
        if ($commandeAchat->articles && $commandeAchat->articles->isNotEmpty()) {
            foreach ($commandeAchat->articles as $articlecommandeAchat) {
                $response['articles'][] = [
                    'id_article' => $articlecommandeAchat->id_article,
                    'nom_article' => optional($articlecommandeAchat->article)->nom_article,
                    'TVA' => $articlecommandeAchat->TVA_article,
                    'quantite_article' => $articlecommandeAchat->quantite_article,
                    'prix_unitaire_article' => $articlecommandeAchat->prix_unitaire_article,
                    'prix_total_tva_article' => $articlecommandeAchat->prix_total_tva_article,
                    'prix_total_article' => $articlecommandeAchat->prix_total_article,
                    'reduction_article' => $articlecommandeAchat->reduction_article,
                ];
            }
        }
    
        return response()->json($response, 200);
    }
    

public function modifierCommandeAchat(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->commande_achat && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $commandeAchat = CommandeAchat::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'num_commandeAchat' => 'required|string',
        'date_commandeAchat'=>'required|date',
        'date_livraison'=>'nullable|date',
        'total_TTC'=>'nullable|numeric',
        'titre'=>'nullable|string',
        'description'=>'nullable|string',
        'active_Stock'=> false,
        'statut_commande'=> 'nullable|in:commander,recu,annuler,brouillon',
        'fournisseur_id'=> 'nullable|exists:fournisseurs,id',
        'depense_id'=> 'nullable|exists:depenses,id',
        'commentaire'=> 'nullable|string',
        'note_interne' => 'nullable|string',
        'doc_interne' => 'nullable|string',
        'articles' => 'nullable|array',
        'articles.*.id_article' => 'required|exists:articles,id',
        'articles.*.quantite_article' => 'required|integer',
        'articles.*.prix_unitaire_article' => 'required|numeric',
        'articles.*.TVA_article' => 'nullable|numeric',
        'articles.*.reduction_article' => 'nullable|numeric',
        'articles.*.prix_total_article'=>'nullable|numeric',
        'articles.*.prix_total_tva_article'=>'nullable|numeric'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $commandeAchat->update($request->except('articles'));

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Commandes d\'achats ont été modifiés',
        'id_commandeAchat' => $commandeAchat->id

    ]);

    if ($request->has('articles')) {
        // Supprimer les articles existants
        $commandeAchat->articles()->delete();

        // Ajouter les nouveaux articles
        foreach ($request->articles as $articleData) {
            ArticleCommandeAchat::create([
                'id_CommandeAchat' => $commandeAchat->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $articleData['quantite_article'],
                'prix_unitaire_article' => $articleData['prix_unitaire_article'],
                'TVA_article' => $articleData['TVA_article'] ?? 0,
                'reduction_article' => $articleData['reduction_article'] ?? 0,
                'prix_total_article' => $articleData['prix_total_article'],
                'prix_total_tva_article' => $articleData['prix_total_tva_article'],
            ]);
        }
    }

    return response()->json(['message' => 'Commande modifiée avec succès', 'commande' => $commandeAchat], 200);
}

public function supprimerCommandeAchat($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->commande_achat && !$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $commandeAchat = CommandeAchat::findOrFail($id);
    $commandeAchat->delete();

    return response()->json(['message' => 'Commande supprimée avec succès'], 200);
}

public function annulerCommandeAchat($id)
{
    $CommandeAchat = CommandeAchat::find($id);

    if (!$CommandeAchat) {
        return response()->json(['error' => 'CommandeAchat non trouvé'], 404);
    }

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

    // Mettre à jour le statut du CommandeAchats en "annuler"
    $CommandeAchat->statut_commande = 'annuler';
    $CommandeAchat->save();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Commandes d\'achats ont été  Annulées',
        'id_commandeAchat' => $CommandeAchat->id
    ]);

    return response()->json(['message' => 'CommandeAchat annulé avec succès', 'CommandeAchat' => $CommandeAchat], 200);
}

public function RecuCommandeAchat($id)
{
    $CommandeAchat = CommandeAchat::find($id);

    if (!$CommandeAchat) {
        return response()->json(['error' => 'CommandeAchat non trouvé'], 404);
    }

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

    // Mettre à jour le statut du CommandeAchats en "annuler"
    $CommandeAchat->statut_commande = 'recu';
    $CommandeAchat->save();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Commandes d\'achats ont été Recus',
        'id_commandeAchat' => $CommandeAchat->id

    ]);
    return response()->json(['message' => 'CommandeAchat Recu avec succès', 'CommandeAchat' => $CommandeAchat], 200);
}

public function exporterCommandesAchats()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'Date');
    $sheet->setCellValue('C1', 'Produits');
    $sheet->setCellValue('D1', 'Fournisseur');
    $sheet->setCellValue('E1', 'Fournisseur - Email');
    $sheet->setCellValue('F1', 'Livraison');
    $sheet->setCellValue('G1', 'Paiement');
    $sheet->setCellValue('H1', 'Total TTC');
    $sheet->setCellValue('J1', 'Note interne');
    $sheet->setCellValue('K1', 'Statut');
    $sheet->setCellValue('L1', 'Titre');

    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $CommandeAchat =CommandeAchat::with(['articles.article', 'fournisseur'])
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $CommandeAchats = CommandeAchat::with(['articles.article', 'fournisseur'])
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                      $query->where('id_user', $userId);
                  });
        })
        ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Remplir les données
    $row = 2;
    foreach ($CommandeAchats as $CommandeAchat) {

        if ($CommandeAchat->articles->isNotEmpty()) {
            $nomarticles = $CommandeAchat->articles->map(function ($articles) {
                return $articles->article ? $articles->article->nom_article : '';
        })->filter()->implode(', ');
            $quantites = $CommandeAchat->articles->map(function ($articles) {
                return $articles->article ? $articles->article->quantite_article : '';
        
            })->filter()->implode(', ');


            $nom_article = $nomarticles ;
            $quantite = $quantites;

            $fournisseurNomComplet = $CommandeAchat->fournisseur 
            ? $CommandeAchat->fournisseur->prenom_fournisseur . ' - ' . $CommandeAchat->fournisseur->nom_fournisseur
            : '';
        

            //dd($nom_article, $quantite);

        $sheet->setCellValue('A' . $row, $CommandeAchat->num_commande);
        $sheet->setCellValue('B' . $row, $CommandeAchat->date_commande);
        $sheet->setCellValue('C' . $row, $nom_article);
        $sheet->setCellValue('D' . $row, $fournisseurNomComplet);
        $sheet->setCellValue('E' . $row, $CommandeAchat->fournisseur ? $CommandeAchat->fournisseur->email_fournisseur : '');
        $sheet->setCellValue('F' . $row, $CommandeAchat->date_livraison);
        $sheet->setCellValue('G' . $row, $CommandeAchat->date_paiement);
        $sheet->setCellValue('H' . $row, $CommandeAchat->total_ttc);
        $sheet->setCellValue('J' . $row, $CommandeAchat->note_interne);
        $sheet->setCellValue('K' . $row, $CommandeAchat->statut_commande);
        $sheet->setCellValue('L' . $row, $CommandeAchat->titre);
        

   

        $row++;
    }
}

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'CommandeAchats.xlsx';

    // Définir les en-têtes HTTP pour le téléchargement
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Générer le fichier et l'envoyer au navigateur
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
}
