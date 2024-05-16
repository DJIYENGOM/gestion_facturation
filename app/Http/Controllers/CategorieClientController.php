<?php

namespace App\Http\Controllers;

use App\Models\CategorieClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreCategorieClientRequest;
use App\Http\Requests\UpdateCategorieClientRequest;

class CategorieClientController extends Controller
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
            'nom_categorie' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $categorie = new CategorieClient([
            'nom_categorie' => $request->nom_categorie,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);
    
        $categorie->save();
    
        return response()->json(['message' => 'Catégorie ajoutée avec succès', 'categorie' => $categorie]);
    }
    
    
    public function listerCategorieClient()
{
    $CategorieClient = CategorieClient::all();
    return response()->json(['CategorieClient' => $CategorieClient]);
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

    $categorie = CategorieClient::findOrFail($id);

    $request->validate([
        'nom_categorie' => 'required|string|max:255',
    ]);

    $categorie->nom_categorie = $request->nom_categorie;
    $categorie->sousUtilisateur_id = $sousUtilisateur_id; 
    $categorie->user_id = $user_id;

    $categorie->save();

    return response()->json(['message' => 'Catégorie modifiée avec succès', 'categorie' => $categorie]);
}

   
public function supprimerCategorie($id)
{
    $categorie = CategorieClient::findOrFail($id);
    $categorie->delete();

    return response()->json(['message' => 'Catégorie supprimée avec succès']);
}
}
