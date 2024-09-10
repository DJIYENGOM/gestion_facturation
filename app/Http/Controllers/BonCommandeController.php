<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\BonCommande;
use App\Models\Notification;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\facture_Etiquette;
use App\Models\ArticleBonCommande;
use App\Models\MessageNotification;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class BonCommandeController extends Controller
{
    public function creerBonCommande(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_commande' => 'nullable|string',
            'reduction_commande' => 'nullable|numeric',
            'date_commande'=>'required|date',
            'date_limite_commande'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'active_Stock'=> 'nullable|in:oui,non',
            'statut_commande'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',
            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'nullable|date',
            'echeances.*.montant_echeance' => 'nullable|numeric|min:0',

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
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour ce sous-utilisateur'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        $typeDocument = 'commande';
        $numBonCommande= NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $commande = BonCommande::create([
            'num_commande' => $numBonCommande,
            'client_id' => $request->client_id,
            'date_commande' => $request->date_commande,
            'date_limite_commande' => $request->date_limite_commande,
            'reduction_commande' => $request->input('reduction_commande', 0),
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_commande' => $request->input('note_commande'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_commande' => $request->statut_commande ?? 'en_attente',
            'active_Stock' => $request->active_Stock ?? 'oui',
        ]);
    
        $commande->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'commande');

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des Bons de Commandes ont été creés',
            'id_bonCommande' => $commande->id

        ]);

        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'bonCommande_id' => $commande->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }

    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleBonCommande::create([
                'id_BonCommande' => $commande->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
        if ($request->has('echeances')) {
            foreach ($request->echeances as $echeanceData) {
                Echeance::create([
                    'bonCommande_id' => $commande->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }

        if ($commande->active_Stock == 'oui') {
            foreach ($commande->articles as $article) {
                if (Stock::where('article_id', $article->id_article)->exists()) {
        
                    // Récupérer le dernier stock pour cet article
                    $lastStock = Stock::where('article_id', $article->id_article)->orderBy('created_at', 'desc')->first();
        
                    $numStock = $lastStock->num_stock;
        
                    // Créer une nouvelle entrée de stock
                    $stock = new Stock();
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock; 
                    $stock->libelle = $lastStock->libelle;
                    $stock->disponible_avant = $lastStock->disponible_avant;
                    $stock->modif = $article->quantite_article;
                    $stock->disponible_apres = $lastStock->disponible_apres - $article->quantite_article;
                    $stock->article_id = $article->id_article;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = $commande->id;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateurId;
                    $stock->user_id = $userId;
                    $stock->save();
                }
                $article = Article::find($article->id_article);

                $article->quantite_disponible = $stock->disponible_apres;
                $article->save();
        
                $notificationConfig = Notification::where('user_id', $article->user_id)
                    ->orWhere('sousUtilisateur_id', $sousUtilisateurId)
                    ->first();

             if ( $notificationConfig->produit_rupture && $notificationConfig->quantite_produit > 0) {
                if ($article->quantite_disponible <= $notificationConfig->quantite_produit) {
                MessageNotification::create([
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                    'article_id' => $article->id,
                    'message' => 'Poduits ont moins de ' . $notificationConfig->quantite_produit . ' dans leur stock',
                ]);
                }
          }

            }
        }
        

        return response()->json(['message' => 'commande créée avec succès', 'commande' => $commande], 201);

    }

    public function TransformeBonCommandeEnFacture($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $BonCommande = BonCommande::find($id);
    if (!$BonCommande) {
        return response()->json(['error' => 'BonCommande non trouvé'], 404);
    }
    $BonCommande->statut_commande = 'transformer';
    $BonCommande->save();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Bons de Commandes ont été transformés en Facture',
        'id_bonCommande' => $BonCommande->id

    ]);
    return response()->json(['message' => 'BonCommande transformée avec succès', 'BonCommande' => $BonCommande], 200);

   
}

public function annulerBonCommande($id)
{
    $BonCommande = BonCommande::find($id);

    if (!$BonCommande) {
        return response()->json(['error' => 'BonCommande non trouvé'], 404);
    }

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Mettre à jour le statut du BonCommandes en "annuler"
    $BonCommande->statut_commande = 'annuler';
    $BonCommande->save();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Bons de Commandes ont été Annulés',
        'id_bonCommande' => $BonCommande->id
    ]);

    return response()->json(['message' => 'BonCommande annulé avec succès', 'BonCommande' => $BonCommande], 200);
}

public function listerTousBonCommande()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $BonCommandes = BonCommande::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = BonCommande::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
// Construire la réponse avec les détails des BonCommandes et les noms des clients
$response = [];
foreach ($BonCommandes as $BonCommande) {
    $response[] = [
        'id' => $BonCommande->id,
        'num_BonCommande' => $BonCommande->num_commande,
        'date_BonCommande' => $BonCommande->date_commande,
        'statut_BonCommande' => $BonCommande->statut_commande,
        'date_limite' => $BonCommande->date_limite_commande,
        'prix_Ht' => $BonCommande->prix_HT,
        'prix_Ttc' => $BonCommande->prix_TTC,
        'note_BonCommande' => $BonCommande->note_commande,
        'prenom_client' => $BonCommande->client->prenom_client, 
        'nom_client' => $BonCommande->client->nom_client, 
        'active_Stock' => $BonCommande->active_Stock,
        'reduction_commande' => $BonCommande->reduction_commande,
        'articles' => $BonCommande->articles->map(function ($articleBonCommande) {
                return [
                    'id' => $articleBonCommande->article->id,
                    'nom' => $articleBonCommande->article->nom_article,
                    'quantite' => $articleBonCommande->quantite_article,
                    'prix' => $articleBonCommande->prix_total_tva_article,
                ];
            }),
            'etiquettes' => $BonCommande->Etiquettes->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all(),
    ];
}

return response()->json(['BonCommandes' => $response]);
}


public function listeBonCommandeParClient($clientId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $BonCommandes = BonCommande::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = BonCommande::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
// Construire la réponse avec les détails des BonCommandes et les noms des clients
$response = [];
foreach ($BonCommandes as $BonCommande) {
    $response[] = [
        'id' => $BonCommande->id,
        'num_BonCommande' => $BonCommande->num_commande,
        'date_BonCommande' => $BonCommande->date_commande,
        'statut_BonCommande' => $BonCommande->statut_commande,
        'date_limite' => $BonCommande->date_limite_commande,
        'prix_Ht' => $BonCommande->prix_HT,
        'prix_Ttc' => $BonCommande->prix_TTC,
        'nom_client' => $BonCommande->client->nom_client, 
        'active_Stock' => $BonCommande->active_Stock,
        'reduction_commande' => $BonCommande->reduction_commande,
        'articles' => $BonCommande->articles->map(function ($articleBonCommande) {
                return [
                    'id' => $articleBonCommande->article->id,
                    'nom' => $articleBonCommande->article->nom_article,
                    'quantite' => $articleBonCommande->quantite_article,
                    'prix' => $articleBonCommande->prix_total_tva_article,
                ];
            }),
        'etiquettes' => $BonCommande->Etiquettes->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all(),
    ];
}

return response()->json(['BonCommandes' => $response]);
}
public function supprimerBonCommande($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $BonCommande = BonCommande::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($BonCommande){
                $BonCommande->archiver = 'oui';
                $BonCommande->save();
            return response()->json(['message' => 'BonCommande supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet BonCommande'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommande = BonCommande::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($BonCommande){
                    $BonCommande->archiver = 'oui';
                    $BonCommande->save();
                return response()->json(['message' => 'BonCommande supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet BonCommande'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function DetailsBonCommande($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
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
    // Rechercher la facture par son numéro
    $bonCommande = BonCommande::where('id', $id)
                ->with(['client', 'articles.article', 'Etiquettes.etiquette', 'echeances'])
                ->first();

    // Vérifier si la bonCommande existe
    if (!$bonCommande) {
        return response()->json(['error' => 'bonCommande non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($bonCommande->date_commande);

    // Préparer la réponse
    $response = [
        'id_bonCommande' => $bonCommande->id,
        'numero_bonCommande' => $bonCommande->num_commande,
        'date_creation' => $dateCreation->format('Y-m-d H:i:s'),
        'date_limite' => $bonCommande->date_limite_commande,
        'client' => [
            'id' => $bonCommande->client->id,
            'nom' => $bonCommande->client->nom_client,
            'prenom' => $bonCommande->client->prenom_client,
            'adresse' => $bonCommande->client->adress_client,
            'telephone' => $bonCommande->client->tel_client,
            'nom_entreprise'=> $bonCommande->client->nom_entreprise,
        ],
        'note_bonCommande' => $bonCommande->note_commande,
        'prix_HT' => $bonCommande->prix_HT,
        'prix_TTC' => $bonCommande->prix_TTC,
        'reduction_bonCommande' => $bonCommande->reduction_commande,
        'statut_bonCommande' => $bonCommande->statut_commande,
        'nom_comptable' => $bonCommande->compteComptable->nom_compte_comptable ?? null,
        'articles' => [],
        'echeances' => [],
        'nombre_echeance' => $bonCommande->echeances ? $bonCommande->echeances->count() : 0,
        'active_Stock' => $bonCommande->active_Stock,

        'etiquettes' => $bonCommande->Etiquettes->map(function ($etiquette) {
            return [
                'id' => optional($etiquette->etiquette)->id,
                'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
            ];
        })->filter(function ($etiquette) {
            return !is_null($etiquette['id']);
        })->values()->all(),
    ];

    // Vérifier si 'articles' est non nul et une collection
    if ($bonCommande->articles && $bonCommande->articles->isNotEmpty()) {
        foreach ($bonCommande->articles as $articlebonCommande) {
            $response['articles'][] = [
                'id_article' => $articlebonCommande->id_article,
                'nom_article' => $articlebonCommande->article->nom_article,
                'TVA' => $articlebonCommande->TVA_article,
                'quantite_article' => $articlebonCommande->quantite_article,
                'prix_unitaire_article' => $articlebonCommande->prix_unitaire_article,
                'prix_total_tva_article' => $articlebonCommande->prix_total_tva_article,
                'prix_total_article' => $articlebonCommande->prix_total_article,
                'reduction_article' => $articlebonCommande->reduction_article,
            ];
        }
    }

    // Vérifier si 'echeances' est non nul et une collection
    if ($bonCommande->echeances && $bonCommande->echeances->isNotEmpty()) {
        foreach ($bonCommande->echeances as $echeance) {
            $response['echeances'][] = [
                'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                'montant_echeance' => $echeance->montant_echeance,
            ];
        }
    }

    // Retourner la réponse JSON
    return response()->json(['bonCommande_details' => $response], 200);
}


public function exporterBonCommandes()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'Date vente');
    $sheet->setCellValue('C1', 'Prod / Serv');
    $sheet->setCellValue('D1', 'Numero - Client');
    $sheet->setCellValue('E1', 'Client');
    $sheet->setCellValue('F1', 'Adresse électronique');
    $sheet->setCellValue('G1', 'Total HT');
    $sheet->setCellValue('H1', 'Total TTC');
    $sheet->setCellValue('I1', 'Statut');
    $sheet->setCellValue('J1', 'Note Interne');



    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $BonCommandes = BonCommande::with(['client', 'articles.article'])
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = BonCommande::with(['client', 'articles.article'])
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                      $query->where('id_user', $userId);
                  });
        })
        ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Remplir les données
    $row = 2;
    foreach ($BonCommandes as $BonCommande) {

        if ($BonCommande->articles->isNotEmpty()) {
            $nomarticles = $BonCommande->articles->map(function ($articles) {
                return $articles->article ? $articles->article->nom_article : '';
            })->filter()->implode(', ');

    
            $nom_article = $nomarticles;
        

        $sheet->setCellValue('A' . $row, $BonCommande->num_commande);
        $sheet->setCellValue('B' . $row, $BonCommande->date_commande);
        $sheet->setCellValue('C' . $row, $nom_article);
        $sheet->setCellValue('D' . $row, $BonCommande->client->num_client);
        $sheet->setCellValue('E' . $row, $BonCommande->client->nom_client . ' - ' . $BonCommande->client->prenom_client);
        $sheet->setCellValue('F' . $row, $BonCommande->client->email);
        $sheet->setCellValue('G' . $row, $BonCommande->prix_HT);
        $sheet->setCellValue('H' . $row, $BonCommande->prix_TTC);
        $sheet->setCellValue('I' . $row, $BonCommande->statut_commande);
        $sheet->setCellValue('J' . $row, $BonCommande->note_commande);

        $row++;
    }
}

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'BonCommandes.xlsx';

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
