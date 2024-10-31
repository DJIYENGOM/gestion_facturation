<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\ModelDocument;


class BonCommandeController extends Controller
{
    public function creerBonCommande(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'num_commande'=>'nullable|string',
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
            'num_commande' => $request->num_commande ?? $numBonCommande,
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
        Artisan::call(command: 'optimize:clear');

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
                    Artisan::call(command: 'optimize:clear');

                $articleDB = Article::find($article->id_article);
                $articleDB->quantite_disponible = $stock->disponible_apres;
                $articleDB->save();
                }
                
                $articleDB = Article::find($article->id_article);

                $notificationConfig = Notification::where('user_id', $article->user_id)
                    ->orWhere('sousUtilisateur_id', $sousUtilisateurId)
                    ->first();

             if ( $notificationConfig->produit_rupture && $notificationConfig->quantite_produit > 0) {
                if ($article->quantite_disponible <= $notificationConfig->quantite_produit) {
                MessageNotification::create([
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                    'article_id' => $articleDB->id,
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
        Artisan::call(command: 'optimize:clear');

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
        Artisan::call(command: 'optimize:clear');

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

        $BonCommandes = Cache::remember('BonCommande', 3600, function () use ($sousUtilisateurId, $userId) {
         
       return BonCommande::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = Cache::remember('BonCommande', 3600, function () use ($userId) {
            
        return BonCommande::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
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

        $BonCommandes = Cache::remember('BonCommande', 3600, function () use ($sousUtilisateurId, $userId, $clientId) {
            return BonCommande::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = Cache::remember('BonCommande', 3600, function () use ($userId, $clientId) {
         
            return BonCommande::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
        });
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
                Artisan::call(command: 'optimize:clear');

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
                    Artisan::call(command: 'optimize:clear');

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
    $bonCommande = Cache::remember('BonCommande',3600, function () use ($sousUtilisateur_id, $user_id, $id) {
     
            return BonCommande::where('id', $id)
                ->with(['client', 'articles.article', 'Etiquettes.etiquette', 'echeances'])
                ->first();
              });

    if (!$bonCommande) {
        return response()->json(['error' => 'bonCommande non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($bonCommande->date_commande);

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

public function genererPDFBonCommande($bonCommandeId, $modelDocumentId)
{
    // 1. Récupérer le bon de commande et le modèle de document depuis la base de données
    $bonCommande = BonCommande::with(['user', 'client', 'articles.article', 'echeances'])->find($bonCommandeId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$bonCommande || !$modelDocument) {
        return response()->json(['error' => 'Bon de commande ou modèle introuvable'], 404);
    }

    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('{{num_commande}}', $bonCommande->num_commande, $content);
    $content = str_replace('{{expediteur_nom}}', $bonCommande->user->name, $content);
    $content = str_replace('{{expediteur_email}}', $bonCommande->user->email, $content);
    $content = str_replace('{{expediteur_tel}}', $bonCommande->user->tel_entreprise ?? 'N/A', $content);
if($bonCommande->client_id){
    $content = str_replace('{{destinataire_nom}}', $bonCommande->client->prenom_client . ' ' . $bonCommande->client->nom_client, $content);
    $content = str_replace('{{destinataire_email}}', $bonCommande->client->email_client, $content);
    $content = str_replace('{{destinataire_tel}}', $bonCommande->client->tel_client, $content);
}
    $content = str_replace('{{date_commande}}', \Carbon\Carbon::parse($bonCommande->created_at)->format('d/m/Y'), $content);

    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($bonCommande->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " fcfa</td>
            <td>" . number_format($article->prix_total_article, 2) . " fcfa</td>
        </tr>";
    }
    $content = str_replace('{{articles}}', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('{{montant_total}}', number_format($bonCommande->prix_TTC, 2) . " fcfa", $content);

    // Gérer les échéances s'il y en a
    if ($bonCommande->echeances && count($bonCommande->echeances) > 0) {
        $echeancesHtml = '';
        foreach ($bonCommande->echeances as $echeance) {
            $echeancesHtml .= "<tr>
                <td>" . \Carbon\Carbon::parse($echeance->date_pay_echeance)->format('d/m/Y') . "</td>
                <td>" . number_format($echeance->montant_echeance, 2) . " fcfa</td>
            </tr>";
        }
        $content = str_replace('{{echeances}}', $echeancesHtml, $content);
    } else {
        $content = str_replace('{{echeances}}', '', $content);
    }

    // Gérer les conditions de paiement si présentes dans le modèle de document
    if ($modelDocument->conditionsPaiementModel) {
        $conditionsPaiementHtml = "<h3>Conditions de paiement</h3><p>{$modelDocument->conditionPaiement}</p>";
        $content = str_replace('{{conditions_paiement}}', $conditionsPaiementHtml, $content);
    } else {
        $content = str_replace('{{conditions_paiement}}', '', $content);
    }

  
    // Gérer les conditions de paiement
    if ($modelDocument->condition_paiement) {
        $conditionsPaiementHtml = "<h3>Conditions de paiement</h3><p>{$modelDocument->condition_paiement}</p>";
        $content = str_replace('conditions_paiement', $conditionsPaiementHtml, $content);
    } else {
        $content = str_replace('conditions_paiement', '', $content);
    }

    // Gérer les coordonnées bancaires
    if ($modelDocument->titulaire_compte && $modelDocument->IBAN && $modelDocument->BIC) {
        $coordonneesBancairesHtml = "<h3>Coordonnées bancaires</h3>
            <p>Titulaire du compte : {$modelDocument->titulaire_compte}</p>
            <p>IBAN : {$modelDocument->IBAN}</p>
            <p>BIC : {$modelDocument->BIC}</p>";
        $content = str_replace('coordonnees_bancaires', $coordonneesBancairesHtml, $content);
    } else {
        $content = str_replace('coordonnees_bancaires', '', $content);
    }

    // 3. Appliquer le CSS du modèle en ajoutant une structure HTML complète
    $css = $modelDocument->css;
    $content = "<!doctype html>
    <html lang='fr'>
    <head>
        <meta charset='utf-8'>
        <style>{$css}</style>
    </head>
    <body>
        {$content}
    </body>
    </html>";

    // 4. Configurer DOMPDF et générer le PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $pdfContent = $dompdf->output();
    $filename = 'bonCommande_' . $bonCommande->num_commande . '.pdf';
    
    return response($pdfContent)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization')
        ->header('Access-Control-Allow-Credentials', 'true')
        ->header('Access-Control-Expose-Headers', 'Content-Disposition');
}

public function RapportCommandeVente(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin'). ' 23:59:59'; //Inclure la fin de la journée
    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;
    $BonCommandes = BonCommande::with(['client'])
    ->whereBetween('created_at', [$dateDebut, $dateFin])
    ->where(function ($query) use ($userId, $parentUserId) {
        $query->where('user_id', $userId)
            ->orWhere('user_id', $parentUserId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                $query->where('id_user', $parentUserId);
            });
    })
    ->get();

    return response()->json($BonCommandes);
}

}
