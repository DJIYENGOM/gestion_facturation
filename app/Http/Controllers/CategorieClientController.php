<?php

namespace App\Http\Controllers;

use App\Models\CategorieClient;
use Illuminate\Http\Request;

use App\Http\Requests\StoreCategorieClientRequest;
use App\Http\Requests\UpdateCategorieClientRequest;

class CategorieClientController extends Controller
{
    public function ajouterCategorie(Request $request)
    {
        $request->validate([
            'nom_categorie' => 'required|string|max:255',
        ]);
    
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
