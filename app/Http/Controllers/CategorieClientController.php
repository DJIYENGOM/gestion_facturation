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
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        if($sousUtilisateur_id == null){
            return response()->json(['Vous n\'êtes pas connecté en tant que sous-utilisateur']);
        }
        
        $validator=Validator::make($request->all(),[
            'nom_categorie' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
    
        $categorie = new CategorieClient([
            'nom_categorie' => $request->nom_categorie,
            'sousUtilisateur_id' => auth('apisousUtilisateur')->id(), // Ou récupérez l'ID de l'utilisateur connecté de votre façon
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
    $categorie = CategorieClient::findOrFail($id);

    $request->validate([
        'nom_categorie' => 'required|string|max:255',
    ]);

    $categorie->nom_categorie = $request->nom_categorie;
    $categorie->sousUtilisateur_id = auth('apisousUtilisateur')->id(); // Ou récupérez l'ID de l'utilisateur connecté de votre façon

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
