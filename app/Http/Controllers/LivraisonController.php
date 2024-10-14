<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Livraison;
use App\Models\Notification;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\ArticleLivraison;
use App\Models\facture_Etiquette;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\ModelDocument;


class LivraisonController extends Controller
{
    public function ajouterLivraison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_livraison' => 'nullable|string',
            'reduction_livraison' => 'nullable|numeric',
            'date_livraison'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'active_Stock'=> 'nullable|in:oui,non',
            'statut_livraison'=> 'nullable|in:brouillon, preparer, planifier,livrer,annuler',
           
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
        $typeDocument = 'livraison';
        $numlivraison = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        // Création de la livraison
        $livraison = Livraison::create([
            'client_id' => $request->client_id,
            'date_livraison' => $request->date_livraison,
            'reduction_livraison' => $request->input('reduction_livraison', 0),
            'statut_livraison' => $request->statut_livraison ?? 'brouillon',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_livraison' => $request->input('note_livraison'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'num_livraison' => $numlivraison,
            'active_Stock' => $request->active_Stock ?? 'oui',
        ]);
    
        $livraison->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'livraison');
        Artisan::call('optimize:clear');

    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleLivraison::create([
                'id_livraison' => $livraison->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }

        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'livraison_id' => $livraison->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }

        if ($livraison->active_Stock == 'oui') {
            foreach ($livraison->articles as $article) {
                if (Stock::where('article_id', $article->id_article)->exists()) {
        
                    // Récupérer le dernier stock pour cet article
                    $lastStock = Stock::where('article_id', $article->id_article)->orderBy('created_at', 'desc')->first();
        
                    $numStock = $lastStock->num_stock;
        
                    // Créer une nouvelle entrée de stock
                    $stock = new Stock();
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock; 
                    $stock->libelle = $lastStock->libelle;
                    $stock->disponible_avant = $lastStock->disponible_apres;
                    $stock->modif = $article->quantite_article;
                    $stock->disponible_apres = $lastStock->disponible_apres - $article->quantite_article;
                    $stock->article_id = $article->id_article;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = $livraison->id;
                    $stock->sousUtilisateur_id = $sousUtilisateurId;
                    $stock->user_id = $userId;
                    $stock->save();
                    Artisan::call(command: 'optimize:clear');

                $articleDB = Article::find($article->id_article);
                $articleDB->quantite_disponible = $stock->disponible_apres;
                $articleDB->save();
                Artisan::call(command: 'optimize:clear');

                }
               
            }
        }
        
        return response()->json(['message' => 'livraison créée avec succès', 'livraison' => $livraison], 201);

    }

    public function listerToutesLivraisons()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $livraisons = Cache::remember('livraisons', 3600, function () use ($sousUtilisateurId, $userId) {
            
        return Livraison::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $livraisons = Cache::remember('livraisons', 3600, function () use ($userId) {
        
        return Livraison::with('client','articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
// Construire la réponse avec les détails des livraisons et les noms des clients
$response = [];
foreach ($livraisons as $livraison) {
    $response[] = [
        'id' => $livraison->id,
        'num_livraison' => $livraison->num_livraison,
        'date_livraison' => $livraison->date_livraison,
        'statut_livraison' => $livraison->statut_livraison,
        'prix_Ht' => $livraison->prix_HT,
        'prix_Ttc' => $livraison->prix_TTC,
        'note_livraison' => $livraison->note_livraison,
        'prenom_client' => $livraison->client->prenom_client, 
        'nom_client' => $livraison->client->nom_client, 
        'active_Stock' => $livraison->active_Stock,
        'reduction_livraison' => $livraison->reduction_livraison,
        'articles' => $livraison->articles->map(function ($articlelivraison) {
                return [
                    'id' => $articlelivraison->article->id,
                    'nom' => $articlelivraison->article->nom_article,
                    'quantite' => $articlelivraison->quantite_article,
                    'prix' => $articlelivraison->prix_total_tva_article,
                ];
            }),
        'etiquettes' => $livraison->Etiquettes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
          
        
    ];
}

return response()->json(['livraisons' => $response]);
}

public function listerToutesLivraisonsParClient($clientId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $livraisons = Cache::remember('livraisons', 3600, function () use ($sousUtilisateurId, $userId, $clientId) {
         
            return Livraison::with('articles.article', 'Etiquettes.etiquette')
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

        $livraisons = Cache::remember('livraisons', 3600, function () use ($userId, $clientId) {
         
        return Livraison::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
// Construire la réponse avec les détails des livraisons et les noms des clients
$response = [];
foreach ($livraisons as $livraison) {
    $response[] = [
        'id' => $livraison->id,
        'num_livraison' => $livraison->num_livraison,
        'date_livraison' => $livraison->date_livraison,
        'statut_livraison' => $livraison->statut_livraison,
        'prix_Ht' => $livraison->prix_HT,
        'prix_Ttc' => $livraison->prix_TTC,
        'note_livraison' => $livraison->note_livraison,
        'prenom_client' => $livraison->client->prenom_client, 
        'nom_client' => $livraison->client->nom_client, 
        'active_Stock' => $livraison->active_Stock,
        'reduction_livraison' => $livraison->reduction_livraison,
        'articles' => $livraison->articles->map(function ($articlelivraison) {
                return [
                    'id' => $articlelivraison->article->id,
                    'nom' => $articlelivraison->article->nom_article,
                    'quantite' => $articlelivraison->quantite_article,
                    'prix' => $articlelivraison->prix_total_tva_article,
                ];
            }),
        'etiquettes' => $livraison->Etiquettes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
    ];
}

return response()->json(['livraisons' => $response]);
}

public function supprimerLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->archiver = 'oui';
                $livraison->save();
            return response()->json(['message' => 'livraison supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->archiver = 'oui';
                    $livraison->save();
                    Artisan::call('optimize:clear');
                return response()->json(['message' => 'livraison supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function PlanifierLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'planifier';
                $livraison->save();
                Artisan::call('optimize:clear');

            return response()->json(['message' => 'livraison planifier avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas planifier cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'planifier';
                    $livraison->save();
                    Artisan::call('optimize:clear');
                return response()->json(['message' => 'livraison planifier avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas planifier cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function RealiserLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'livrer';
                $livraison->save();
                Artisan::call('optimize:clear');

            return response()->json(['message' => 'livraison realiser avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas realiser cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'livrer';
                    $livraison->save();
                    Artisan::call('optimize:clear');

                return response()->json(['message' => 'livraison realiser avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas realiser cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function LivraisonPreparer($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'preparer';
                $livraison->save();
                Artisan::call('optimize:clear');

            return response()->json(['message' => 'livraison preparer avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas preparer cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'preparer';
                    $livraison->save();
                    Artisan::call('optimize:clear');
                return response()->json(['message' => 'livraison preparer avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas preparer cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function transformerLivraisonEnFacture(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
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
    $livraison = Livraison::find($id);
    if (!$livraison) {
        return response()->json(['error' => 'livraison non trouvé'], 404);
    }

    $livraison->statut_livraison = 'livrer';
    $livraison->save();
    Artisan::call('optimize:clear');


    return response()->json(['message' => 'livraison transformée en facture avec succès'], 201);
}

public function DetailsLivraison($id)
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
    $livraison = Cache::remember('livraison', 3600, function () use ($id) {
        
        return Livraison::where('id', $id)
                ->with(['client', 'articles.article', 'Etiquettes.etiquette'])
                ->first();
    });
    if (!$livraison) {
        return response()->json(['error' => 'livraison non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $date_livraison = Carbon::parse($livraison->date_livraison);

    $response = [
        'id_livraison' => $livraison->id,
        'numero_livraison' => $livraison->num_livraison,
        'date_livraison' => $date_livraison->format('Y-m-d H:i:s'),
        'client' => [
            'id' => $livraison->client->id,
            'nom' => $livraison->client->nom_client,
            'prenom' => $livraison->client->prenom_client,
            'adresse' => $livraison->client->adress_client,
            'telephone' => $livraison->client->tel_client,
            'nom_entreprise'=> $livraison->client->nom_entreprise,
        ],
        'note_livraison' => $livraison->note_livraison,
        'prix_HT' => $livraison->prix_HT,
        'prix_TTC' => $livraison->prix_TTC,
        'reduction_livraison' => $livraison->reduction_livraison,
        'statut_livraison' => $livraison->statut_livraison,
        'nom_comptable' => $livraison->compteComptable->nom_compte_comptable ?? null,
        'articles' => [],
        'active_Stock' => $livraison->active_Stock,
        
        'etiquettes' => $livraison->Etiquettes->map(function ($etiquette) {
            return [
                'id' => optional($etiquette->etiquette)->id,
                'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
            ];
        })->filter(function ($etiquette) {
            return !is_null($etiquette['id']);
        })->values()->all(),
    ];

    // Vérifier si 'articles' est non nul et une collection
    if ($livraison->articles && $livraison->articles->isNotEmpty()) {
        foreach ($livraison->articles as $articlelivraison) {
            $response['articles'][] = [
                'id_article' => $articlelivraison->id_article,
                'nom_article' => $articlelivraison->article->nom_article,
                'TVA' => $articlelivraison->TVA_article,
                'quantite_article' => $articlelivraison->quantite_article,
                'prix_unitaire_article' => $articlelivraison->prix_unitaire_article,
                'prix_total_tva_article' => $articlelivraison->prix_total_tva_article,
                'prix_total_article' => $articlelivraison->prix_total_article,
                'reduction_article' => $articlelivraison->reduction_article,
            ];
        }
    }

    return response()->json(['bonCommande_details' => $response], 200);
}


public function exporterLivraisons()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'Livraison');
    $sheet->setCellValue('C1', 'Prod / Serv');
    $sheet->setCellValue('D1', 'Numero - Client');
    $sheet->setCellValue('E1', 'Client');
    $sheet->setCellValue('F1', 'Adresse électronique');
    $sheet->setCellValue('G1', 'Total HT');
    $sheet->setCellValue('H1', 'Total TTC');
    $sheet->setCellValue('I1', 'Fournisseur');
    $sheet->setCellValue('K1', 'Statut');
    $sheet->setCellValue('J1', 'Note Interne');
    



    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $Livraisons = Livraison::with(['client', 'articles.article', 'fournisseur'])
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $Livraisons = Livraison::with(['client', 'articles.article', 'fournisseur'])
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
    foreach ($Livraisons as $livraison) {

        if ($livraison->articles->isNotEmpty()) {
            $nomarticles = $livraison->articles->map(function ($articles) {
                return $articles->article ? $articles->article->nom_article : '';
            })->filter()->implode(', ');


            $nom_article = $nomarticles;
        

        $sheet->setCellValue('A' . $row, $livraison->num_livraison);
        $sheet->setCellValue('B' . $row, $livraison->date_livraison);
        $sheet->setCellValue('C' . $row, $nom_article);
        $sheet->setCellValue('D' . $row, $livraison->client->num_client);
        $sheet->setCellValue('E' . $row, $livraison->client->nom_client . ' - ' . $livraison->client->prenom_client);
        $sheet->setCellValue('F' . $row, $livraison->client->email_client);
        $sheet->setCellValue('G' . $row, $livraison->prix_HT);
        $sheet->setCellValue('H' . $row, $livraison->prix_TTC);
        $sheet->setCellValue('I' . $row, $livraison->fournisseur->nom_fournisseur);
        $sheet->setCellValue('K' . $row, $livraison->statut_livraison);
        $sheet->setCellValue('J' . $row, $livraison->note_livraison);

        $row++;
    }
  }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Livraisons.xlsx';

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

public function genererPDFLivraison($livraisonId, $modelDocumentId)
{
    // 1. Récupérer la livraison et le modèle de document depuis la base de données
    $livraison = Livraison::with(['user', 'client', 'articles.article'])->find($livraisonId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$livraison || !$modelDocument) {
        return response()->json(['error' => 'Livraison ou modèle introuvable'], 404);
    }

    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('{{num_livraison}}', $livraison->num_livraison, $content);
    $content = str_replace('{{expediteur_nom}}', $livraison->user->name, $content);
    $content = str_replace('{{expediteur_email}}', $livraison->user->email, $content);
    $content = str_replace('{{expediteur_tel}}', $livraison->user->tel_entreprise ?? 'N/A', $content);

    $content = str_replace('{{destinataire_nom}}', $livraison->client->prenom_client . ' ' . $livraison->client->nom_client, $content);
    $content = str_replace('{{destinataire_email}}', $livraison->client->email_client, $content);
    $content = str_replace('{{destinataire_tel}}', $livraison->client->tel_client, $content);

    $content = str_replace('{{date_livraison}}', \Carbon\Carbon::parse($livraison->created_at)->format('d/m/Y'), $content);

    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($livraison->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " fcfa</td>
            <td>" . number_format($article->prix_total_article, 2) . " fcfa</td>
        </tr>";
    }
    $content = str_replace('{{articles}}', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('{{montant_total}}', number_format($livraison->prix_TTC, 2) . " fcfa", $content);

    // 3. Appliquer le CSS du modèle
    $css = $modelDocument->css;
    $content = str_replace('{{css}}', $css, $content);

    // 4. Configurer DOMPDF et générer le PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);  // Si vous avez des images distantes
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 5. Télécharger le PDF
    return $dompdf->stream('livraison_' . $livraison->num_livraison . '.pdf');
}

public function RapportLivraison(Request $request)
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
    $Livraisons = Livraison::with(['client'])
    ->whereBetween('created_at', [$dateDebut, $dateFin])
    ->where(function ($query) use ($userId, $parentUserId) {
        $query->where('user_id', $userId)
            ->orWhere('user_id', $parentUserId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                $query->where('id_user', $parentUserId);
            });
    })
    ->get();

    return response()->json($Livraisons);
}

}
