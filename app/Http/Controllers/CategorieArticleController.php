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
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'nom_categorie_article' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $categorie = new CategorieArticle([
            'nom_categorie_article' => $request->categorie_articles,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);
    
        $categorie->save();
    
        return response()->json(['message' => 'Catégorie ajoutée avec succès', 'categorie' => $categorie]);
    }
    
    
    public function listerCategorie()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $categories = CategorieArticle::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $categories = CategorieArticle::where('user_id', $userId)
                ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json(['CategorieClient' => $categories]);
    }    

    public function modifierCategorie(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $categorie = CategorieArticle::findOrFail($id);

    $request->validate([
        'nom_categorie_article' => 'required|string|max:255',
    ]);

    $categorie->nom_categorie_article = $request->nom_categorie_article;
    $categorie->sousUtilisateur_id = $sousUtilisateur_id; 
    $categorie->user_id = $user_id;

    $categorie->save();

    return response()->json(['message' => 'Catégorie modifiée avec succès', 'categorie' => $categorie]);
}

   
public function supprimerCategorie($id)
{
    $categorie = CategorieArticle::findOrFail($id);
    $categorie->delete();

    return response()->json(['message' => 'Catégorie supprimée avec succès']);
}
}
