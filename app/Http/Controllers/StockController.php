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
            'libelle' => $stock->libelle,
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
            'libelle' => $stock->libelle,
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

    // Vérifier si la réponse contient les stocks
    if (!isset($response->stocks)) {
        return response()->json(['error' => 'Impossible de récupérer les stocks à modifier'], 500);
    }

    $stocks = collect($response->stocks); // Convertir en collection pour faciliter la manipulation

    foreach ($request->input('stock_modifier') as $stock_modif) {
        $stockId = $stock_modif['id_stock'];
        $quantiteAjoutee = $stock_modif['quantite_ajoutee'];

        // Trouver le stock correspondant à partir de la liste récupérée
        $stock = $stocks->firstWhere('id', $stockId);

        // Si le stock existe et que la quantité ajoutée est différente de 0
        if ($stock && $quantiteAjoutee != 0) {
            Stock::create([
                'num_stock' => $stock->Code,
                'date_stock' => now(),
                'libelle' => $stock->libelle,
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



function Rapport_Valeur_Stock($date)
{
    $stocks = Stock::where('date_stock', '<=', $date)->get();

    $rapport = [];
    
    foreach ($stocks as $stock) {
        $article = $stock->article; 
        $code = $article->num_article; 
        $produit = $stock->libelle; 
        $quantite_totale = $stock->modif; 
        $quantite_facture = 0;
        if($stock->facture){
            $quantite_facture += $stock->modif;
        }
        $prix_achat_ht = $article->prix_ht_achat; 
        $tva_achat = $article->tva_achat; 
        $prix_ttc_achat = $article->prix_ttc_achat; 
        
        // Calcul de la valeur totale en HT et TTC
        $valeur_totale_ht = $quantite_totale * $prix_achat_ht;
        $valeur_totale_ttc = $quantite_totale * $prix_ttc_achat;
        
        // Vérification pour éviter les doublons par article
        if (!isset($rapport[$code])) {
            $rapport[$code] = [
                'code' => $code,
                'produit' => $produit,
                'quantite' => $quantite_totale,
                'prix_achat_ht' => $prix_achat_ht,
                'prix_ttc_achat' => $prix_ttc_achat,
                'tva_achat' => $tva_achat,
                'valeur_totale_ht' => $valeur_totale_ht,
                'valeur_totale_ttc' => $valeur_totale_ttc
            ];
        } else {
            // Si l'article existe déjà, on additionne les quantités et on recalcule les valeurs
            $rapport[$code]['quantite'] += $quantite_totale - (2* $quantite_facture);
            $rapport[$code]['valeur_totale_ht'] += $valeur_totale_ht;
            $rapport[$code]['valeur_totale_ttc'] += $valeur_totale_ttc;
        }
    }

    return $rapport;
}

 
public function calculerValeurStock(Request $request)
{
    // Vérification des accès utilisateur
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();

        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin && !$sousUtilisateur->gestion_stock) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;

    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Validation des données de la requête
    $validator = Validator::make($request->all(), [
        'date' => 'required|date',
        'FIFO' => 'nullable|boolean',
        'prix_achat_moyen' => 'nullable|boolean',
        'prix_achat_actuel' => 'nullable|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Récupération des stocks en fonction de la date spécifiée
    $stocks = Stock::where('date_stock', '<=', $request->date)->get();

    // Vérification des méthodes fournies
    $useFIFO = $request->get('FIFO', false);
    $useCUMP = $request->get('prix_achat_moyen', false);
    $useLIFO = $request->get('prix_achat_actuel', false);
    
    // Grouping stocks by product number
    $groupedStocks = $stocks->groupBy('num_stock'); // Regroupement des stocks par produit
    
    // Initialisation des résultats par produit
    $results = [];
    
    foreach ($groupedStocks as $numeroProduit => $stocksForProduct) {
        // Initialisation des variables pour les calculs par produit
        $quantite_totale = 0;
        $valeur_totale = 0;
        $valeur_facture = 0;
        $valeur_commandeAchat = 0;
    
        if ($useFIFO) {
            // Méthode FIFO (First In First Out)
            foreach ($stocksForProduct as $stock) {
                $lastStock = $stocks->last();
                $quantite_totale = $lastStock->disponible_apres; 
                $valeur_totale += $stock->modif * $stock->article->prix_achat_ht;
    
                if ($stock->facture_id != null) {
                    $valeur_facture += $stock->modif * $stock->article->prix_achat_ht;
                    $valeur_totale -= $valeur_facture;
                }
    
                if ($stock->commandeAchat_id != null) {
                    $valeur_commandeAchat += $stock->modif * $stock->commandeAchat->prix_achat_ht;
                    $valeur_totale += $valeur_commandeAchat;
                }
            }
        }
    
        if ($useCUMP) {
            // Méthode CUMP (Coût Unitaire Moyen Pondéré)
            $quantite_totale = $stocksForProduct->sum('modif');
            $valeur_totale = $stocksForProduct->sum(function ($stock) {
                return $stock->modif * $stock->article->prix_achat_ht;
            });
    
            $prix_achat_moyen = $quantite_totale > 0 ? $valeur_totale / $quantite_totale : 0;
        }
    
        if ($useLIFO) {
            // Méthode LIFO (Last In First Out)
            $lastStock = $stocksForProduct->last(); // Dernier stock ajouté
            $quantite_totale = $stocksForProduct->sum('modif');
            $valeur_totale = $quantite_totale * $lastStock->article->prix_achat_ht;
        }
    
        // Stocker les résultats pour chaque produit
        $results[$numeroProduit] = [
            'quantite_totale' => $quantite_totale,
            'valeur_totale' => $valeur_totale,
        ];
    }
    
    // Retourner les résultats sous forme de réponse JSON
    return response()->json($results);
}

}