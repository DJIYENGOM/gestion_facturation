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
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $categories = CategorieClient::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $categories = CategorieClient::where('user_id', $userId)
                ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        return response()->json(['CategorieClient' => $categories]);
    }    

    public function modifierCategorie(Request $request, $id)
{
    $categorie = CategorieClient::findOrFail($id);

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        if ($categorie->sousUtilisateur_id !== $sousUtilisateur_id) {
            return response()->json(['error' => 'cette sous utilisateur ne peut pas modifier ce categorie car il ne l\'a pas creer'], 401);
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

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $CategorieClient = CategorieClient::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($CategorieClient){
                $CategorieClient->delete();
            return response()->json(['message' => 'CategorieClient supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce CategorieClient'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $CategorieClient = CategorieClient::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
            if($CategorieClient){
                $CategorieClient->delete();
                return response()->json(['message' => 'CategorieClient supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce CategorieClient'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

}
}
