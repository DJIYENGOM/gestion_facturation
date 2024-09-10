<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
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
    
            $stocks = Stock::with('facture','bonCommande','livraison')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->orderByDesc('created_at')
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $stocks = Stock::with('facture','bonCommande','livraison')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                })
                ->orderByDesc('created_at')
                ->get();
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
        $stocks = Stock::where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->orderBy('num_stock')
            ->orderByDesc('created_at')
            ->get()
            ->unique('num_stock'); // Garder seulement le dernier enregistrement par num_stock
        
    } elseif (auth()->check()) {
        $userId = auth()->id();

        // Récupérer les derniers enregistrements de chaque numéro de stock
        $stocks = Stock::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            })
            ->orderBy('num_stock')
            ->orderByDesc('created_at')
            ->get()
            ->unique('num_stock'); // Garder seulement le dernier enregistrement par num_stock
        
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
        }
    }

    return response()->json(['message' => 'Stocks modifiés avec succès']);
}


}
