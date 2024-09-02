<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CategorieArticle;
use App\Http\Requests\StoreCategorieArticleRequest;
use App\Http\Requests\UpdateCategorieArticleRequest;

class CategorieArticleController extends Controller
{
    public function ajouterCategorie(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour ce sous-utilisateur'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'nom_categorie_article' => 'required|string|max:255',
            'type_categorie_article' => 'required|in:produit,service',

        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $categorie = new CategorieArticle([
            'nom_categorie_article' => $request->nom_categorie_article,
            'type_categorie_article' => $request->type_categorie_article,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);
    
        $categorie->save();
    
        return response()->json(['message' => 'Catégorie ajoutée avec succès', 'categorie' => $categorie]);
    }
    
    
    public function listerCategorieProduit()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            
            $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $categories = CategorieArticle::where(function($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->where('type_categorie_article', 'produit')
            ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $categories = CategorieArticle::where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sousUtilisateurs', function($subQuery) use ($userId) {
                          $subQuery->where('id_user', $userId);
                      });
            })
            ->where('type_categorie_article', 'produit')
            ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        return response()->json(['CategorieArticle' => $categories]);
    }

    public function listerCategorieService()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $categories = CategorieArticle::where(function($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->where('type_categorie_article', 'service')
            ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $categories = CategorieArticle::where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sousUtilisateurs', function($subQuery) use ($userId) {
                          $subQuery->where('id_user', $userId);
                      });
            })
            ->where('type_categorie_article', 'service')
            ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        return response()->json(['CategorieArticle' => $categories]);
    } 

    public function modifierCategorie(Request $request, $id)
{
    $categorie = CategorieArticle::findOrFail($id);

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id(); 
        if ($categorie->sousUtilisateur_id !== $sousUtilisateur_id) {
            return response()->json(['error' => 'cette sous utilisateur ne peut pas modifier ce CategorieArticle car il ne l\'a pas creer'], 401);
        }
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        if ($categorie->user_id !== $user_id) {
            return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce categorie, car il ne l\'a pas creer'], 401);
        }
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $request->validate([
        'nom_categorie_article' => 'required|string|max:255',
        'type_categorie_article' => 'required|in:produit,service',
    ]);

    $categorie->nom_categorie_article = $request->nom_categorie_article;
    $categorie->type_categorie_article = $request->type_categorie_article;
    $categorie->sousUtilisateur_id = $sousUtilisateur_id; 
    $categorie->user_id = $user_id;

    $categorie->save();

    return response()->json(['message' => 'Catégorie modifiée avec succès', 'categorie' => $categorie]);
}


public function supprimerCategorie($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $CategorieArticle = CategorieArticle::where('id', $id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);

            if($CategorieArticle)
            {
                $CategorieArticle->delete();
            return response()->json(['message' => 'CategorieArticle supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce CategorieArticle'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $CategorieArticle = CategorieArticle::where('id', $id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });

            if($CategorieArticle)
            {
                $CategorieArticle->delete();
                return response()->json(['message' => 'CategorieArticle supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce CategorieArticle'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

}
}
