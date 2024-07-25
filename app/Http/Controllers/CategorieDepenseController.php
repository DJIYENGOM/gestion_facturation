<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategorieDepense;
use Illuminate\Support\Facades\Validator;

class CategorieDepenseController extends Controller
{
    public function ajouterCategorieDepense(Request $request)
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
            'nom_categorie_depense' => 'required|string|max:255',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $categorie = new CategorieDepense([
            'nom_categorie_depense' => $request->nom_categorie_depense,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);
    
        $categorie->save();
    
        return response()->json(['message' => 'Categorie ajoutée avec succès', 'categorie' => $categorie]);
    }

    public function listerCategorieDepense()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $categories = CategorieDepense::where(function($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->orWhere(function($query) {
                    $query->whereNull('user_id')
                          ->whereNull('sousUtilisateur_id');
                })
                ->get();
    
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $categories = CategorieDepense::where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                              $query->where('id_user', $userId);
                          });
                })
                ->orWhere(function($query) {
                    $query->whereNull('user_id')
                          ->whereNull('sousUtilisateur_id');
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json(['CategorieDepense' => $categories]);
    }
    
}