<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
   
    public function listerStocks()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin && !$sousUtilisateur->gestion_stock) {
              return response()->json(['error' => 'Accès non autorisé'], 403);
              }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $stocks = Cache::remember('stocks',3600, function () use ($sousUtilisateurId, $userId) {
            
                return Stock::with('facture','bonCommande','livraison')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->orderByDesc('created_at')
                ->get();
            });
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $stocks = Cache::remember('stocks',3600, function () use ($userId) {
            return Stock::with('facture','bonCommande','livraison')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                })
                ->orderByDesc('created_at')
                ->get();
            });
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    // Construire la réponse avec les détails des stocks et les noms des clients
    $response = [];
    foreach ($stocks as $stock) {
        $response[] = [
            'id' => $stock->id,
            'type_stock' => $stock->type_stock,
            'num_stock' => $stock->num_stock,
            'date_stock' => $stock->date_stock,
            'nom_article' => $stock->nom_article,
            'disponible_avant' => $stock->disponible_avant,
            'modif' => $stock->modif,
            'disponible_apres' => $stock->disponible_apres,
            'active_Stock' => $stock->active_Stock,
            'reduction_stock' => $stock->reduction_stock,
            'facture_id' => $stock->facture_id ?? null,
            'num_facture' => $stock->facture->num_facture ?? null,
            'bonCommande_id' => $stock->bonCommande_id ?? null,
            'num_bonCommande' => $stock->bonCommande->num_commande ?? null,
            'livraison_id' => $stock->livraison_id ?? null,
            'num_livraison' => $stock->livraison->num_livraison ?? null,
            'date_creation' => $stock->created_at,
        ];
    }
    
    return response()->json(['stocks' => $response]);
    }


    public static function ListeStock_a_modifier()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin && !$sousUtilisateur->gestion_stock) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;
        
        // Récupérer les derniers enregistrements de chaque numéro de stock
        $stocks = Cache::remember('stocks',3600, function () use ($sousUtilisateurId, $userId) {
            return  Stock::where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->orderBy('num_stock')
            ->orderByDesc('created_at')
            ->get()
            ->unique('num_stock'); // Garder seulement le dernier enregistrement par num_stock
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        // Récupérer les derniers enregistrements de chaque numéro de stock
        $stocks = Cache::remember('stocks',3600, function () use ($userId) {
            
            return Stock::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            })
            ->orderBy('num_stock')
            ->orderByDesc('created_at')
            ->get()
            ->unique('num_stock'); // Garder seulement le dernier enregistrement par num_stock
        });
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Construire la réponse avec les détails des stocks
    $response = [];
    foreach ($stocks as $stock) {
        $response[] = [
            'id' => $stock->id,
            'type_stock' => $stock->type_stock,
            'Code' => $stock->num_stock,
            'nom_article' => $stock->nom_article,
            'disponible_actuel' => $stock->disponible_apres,
            'disponible_nouveau' => $stock->disponible_apres,
            'quantite_ajoutee' => 0,
            'article_id' => $stock->article_id,
           
        ];
    }

    return response()->json(['stocks' => $response]);
}

public function modifierStock(Request $request)
{
    // Vérification de l'authentification et récupération des IDs utilisateur et sous-utilisateur
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour vous'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Validation des données d'entrée
    $validator = Validator::make($request->all(), [
        'stock_modifier' => 'required|array',
        'stock_modifier.*.id_stock' => 'required|exists:stocks,id',
        'stock_modifier.*.quantite_ajoutee' => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
        ], 422);
    }

    // Appeler la fonction ListeStock_a_modifier et décoder la réponse JSON
    $response = self::ListeStock_a_modifier()->getData();

    if (!isset($response->stocks)) {
        return response()->json(['error' => 'Impossible de récupérer les stocks à modifier'], 500);
    }

    $stocks = collect($response->stocks); // Convertir en collection pour faciliter la manipulation

    foreach ($request->input('stock_modifier') as $stock_modif) {
        $stockId = $stock_modif['id_stock'];
        $quantiteAjoutee = $stock_modif['quantite_ajoutee'];

        // Trouver le stock correspondant à partir de la liste récupérée
        $stock = $stocks->firstWhere('id', $stockId);

        if ($stock && $quantiteAjoutee != 0) {
            Stock::create([
                'num_stock' => $stock->Code,
                'date_stock' => now(),
                'nom_article' => $stock->nom_article,
                'type_stock' => $stock->type_stock,
                'disponible_avant' => $stock->disponible_actuel,
                'modif' => $quantiteAjoutee,
                'disponible_apres' => $stock->disponible_actuel + $quantiteAjoutee,
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'statut_stock' => "Modification manuelle",
                'article_id' => $stock->article_id,
            ]);
            Artisan::call('optimize:clear');
        }
    }

    return response()->json(['message' => 'Stocks modifiés avec succès']);
}



function Rapport_Valeur_Stock(Request $request)
{
    $date = $request->get('date', date('Y-m-d'));

    $stocks = Stock::with(['article', 'facture', 'commandeAchat.articles'])
        ->where('date_stock', '<=', $date)
        ->orderBy('date_stock', 'asc')
        ->get();

    $useFIFO = $request->get('FIFO', true);
    $useCUMP = $request->get('prix_achat_moyen', false);
    $useLIFO = $request->get('prix_achat_actuel', false);

    $rapport = [];
    
    // Grouper les stocks par article
    $groupedStocks = $stocks->groupBy('article_id');

    foreach ($groupedStocks as $articleId => $stocksForProduct) {
        $article = $stocksForProduct->first()->article; 
        if (!$article) {
            continue; // Ignorer si la relation article est manquante
        }

        $code = $article->num_article; 
        $produit = $article->nom_article;
        $date_derniere_entree = $stocksForProduct->last()->date_stock;

        $quantite_totale = 0;
        $quantite_facture = 0;
        $valeur_totale_ht = 0;
        $valeur_totale_ttc = 0;
        $prix_achat_ht = 0;
        $prix_ttc_achat = 0; 

        foreach ($stocksForProduct as $stock) {
            $quantite_totale += $stock->modif;
            if ($stock->facture) {
                $quantite_facture += $stock->modif;
            }
            $quantite_totale_final = $stocksForProduct->last()->disponible_apres;

        }

        if ($useFIFO) {
            // Calcul pour FIFO : On prend les premiers stocks entrés
            $firstStock = $stocksForProduct->filter(function($s) {
                return $s->commandeAchat != null;
            })->first();
            if ($firstStock && $firstStock->commandeAchat->articles->isNotEmpty()) {
                // Récupérer le premier article de la commande d'achat
                $firstArticle = $firstStock->commandeAchat->articles->first();
                $prix_achat_ht = $firstArticle->prix_unitaire_article_ht;
                $prix_ttc_achat = $firstArticle->prix_unitaire_article_ttc;
                $quantite =$firstArticle->modif;
            } else {
                $prix_achat_ht = $article->prix_ht_achat ?? 0;
                $prix_ttc_achat = $article->prix_ttc_achat ?? 0;
                $quantite = $stock->modif;
            }
            
            $valeur_totale_ht = $quantite * $prix_achat_ht;
            $valeur_totale_ttc = $quantite * $prix_ttc_achat;
        } 
        
        elseif ($useCUMP) {
            // Calcul pour CUMP : On prend la moyenne des prix d'achat
            $cump_total_quantite = $stocks->where('article_id', $article->id)->sum('modif');
            $cump_total_ht = $stocks->where('article_id', $article->id)->sum(function ($s) {
                return $s->modif * ($s->commandeAchat && $s->commandeAchat->articles->isNotEmpty() ? $s->commandeAchat->articles->first()->prix_unitaire_article_ht : $s->article->prix_ht_achat);
            });
            $cump_total_ttc = $stocks->where('article_id', $article->id)->sum(function ($s) {
                return $s->modif * ($s->commandeAchat && $s->commandeAchat->articles->isNotEmpty() ? $s->commandeAchat->articles->first()->prix_unitaire_article_ttc : $s->article->prix_ttc_achat );
            });
            $prix_achat_ht = ($cump_total_quantite - $quantite_facture) > 0 ? $cump_total_ht / ($cump_total_quantite - $quantite_facture) : 0;
            $prix_ttc_achat = ($cump_total_quantite - $quantite_facture) > 0 ? $cump_total_ttc / ($cump_total_quantite - $quantite_facture) : 0;
            $valeur_totale_ht = $quantite_totale_final * $prix_achat_ht;
            $valeur_totale_ttc = $quantite_totale_final * $prix_ttc_achat;
        } 
        
        elseif ($useLIFO) {
            // Calcul pour LIFO : On prend les derniers stocks entrés
            $lastStock = $stocksForProduct->filter(function($s) {
                return $s->commandeAchat != null;
            })->last();

            if ($lastStock && $lastStock->commandeAchat->articles->isNotEmpty()) {
                // Récupérer le dernier article de la commande d'achat
                $lastArticle = $lastStock->commandeAchat->articles->last();
                $prix_achat_ht = $lastArticle->prix_unitaire_article_ht ?? 0;
                $prix_ttc_achat = $lastArticle->prix_unitaire_article_ttc ?? 0;
                $quantite = $lastArticle->modif;
            } else {
                $prix_achat_ht = $article->prix_ht_achat ?? 0;
                $prix_ttc_achat = $article->prix_ttc_achat ?? 0;
                $quantite = $stock->modif;
            }
            
            $valeur_totale_ht = $quantite * $prix_achat_ht;
            $valeur_totale_ttc = $quantite * $prix_ttc_achat;
        }

        // Mise à jour du rapport
        if (!isset($rapport[$code])) {
            $rapport[$code] = [
                'code' => $code,
                'produit' => $produit,
                'date_derniere_entree' => $date_derniere_entree,
                'quantite' => $quantite_totale_final,
                'prix_achat_ht' => $prix_achat_ht,
                'prix_ttc_achat' => $prix_ttc_achat,
                'valeur_totale_ht' => $valeur_totale_ht,
                'valeur_totale_ttc' => $valeur_totale_ttc
            ];
        } else {
            $rapport[$code]['quantite'] += $quantite_totale_final;
            $rapport[$code]['valeur_totale_ht'] += $valeur_totale_ht;
            $rapport[$code]['valeur_totale_ttc'] += $valeur_totale_ttc;
        }
    }

    return response()->json($rapport);
}




}