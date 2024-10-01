<?php

namespace App\Http\Controllers;

use App\Models\Etiquette;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class EtiquetteController extends Controller
{
    public function creerEtiquette(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        $validator = Validator::make($request->all(), [
            'nom_etiquette' => 'required|string|max:255',
            'code_etiquette' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $etiquette = new Etiquette([
            'nom_etiquette' => $request->nom_etiquette,
            'code_etiquette' => $request->code_etiquette,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);

        $etiquette->save();
        Artisan::call(command: 'optimize:clear');
        return response()->json(['message' => 'Etiquette ajoutée avec succès', 'etiquette' => $etiquette]);
    }

    public function ListerEtiquette()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $etiquette= Cache::remember('Etiquette', 3600, function () use ($sousUtilisateurId, $userId) {
             
           return Etiquette::where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
            });
        } elseif (auth()->check()) {
            $userId = auth()->id();
          
            $etiquette = Cache::remember('Etiquette', 3600, function () use ($userId) {
             
            return Etiquette::where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
            });
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        return response()->json(['etiquette' => $etiquette], 200);
    }

    public function modifierEtiquette(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $etiquette = Etiquette::where('id', $id)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->first();

    if (!$etiquette) {
        return response()->json(['error' => 'Etiquette introuvable'], 404);
    }

    $validator = Validator::make($request->all(), [
        'nom_etiquette' => 'required|string|max:255',
        'code_etiquette' => 'nullable',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $etiquette->nom_etiquette = $request->nom_etiquette;
    $etiquette->code_etiquette = $request->code_etiquette;
    $etiquette->save();
    Artisan::call(command: 'optimize:clear');

    return response()->json(['message' => 'Etiquette modifiée avec succès', 'etiquette' => $etiquette]);
}

public function supprimerEtiquette($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $etiquette = Etiquette::where('id', $id)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->first();

    if (!$etiquette) {
        return response()->json(['error' => 'Etiquette introuvable'], 404);
    }

    $etiquette->delete();
    Artisan::call(command: 'optimize:clear');

    return response()->json(['message' => 'Etiquette supprimée avec succès']);
}

}
