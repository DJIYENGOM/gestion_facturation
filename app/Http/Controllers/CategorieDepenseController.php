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
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour ce sous-utilisateur'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        return response()->json(['CategorieDepense' => $categories]);
    }

    public function supprimerCategorieDepense($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $CategorieDepense = CategorieDepense::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($CategorieDepense){
                $CategorieDepense->delete();
            return response()->json(['message' => 'CategorieDepense supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce CategorieDepense'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $CategorieDepense = CategorieDepense::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
            if($CategorieDepense){
                $CategorieDepense->delete();
                return response()->json(['message' => 'CategorieDepense supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce CategorieDepense'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

}

public function modifierCategorieDepense(Request $request, $id)
{
    $categorie = CategorieDepense::findOrFail($id);

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
        'nom_categorie_depense' => 'required|string|max:255',
    ]);

    $categorie->nom_categorie_depense = $request->sousUtilisateur_id;
    $categorie->sousUtilisateur_id = $sousUtilisateur_id; 
    $categorie->user_id = $user_id;

    $categorie->save();

    return response()->json(['message' => 'Catégorie modifiée avec succès', 'categorie' => $categorie]);
}
    
}
