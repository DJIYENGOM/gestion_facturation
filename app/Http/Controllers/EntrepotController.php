<?php

namespace App\Http\Controllers;

use App\Models\Entrepot;
use Illuminate\Http\Request;

class EntrepotController extends Controller
{
   
  public function ajouterEntrepot(Request $request)
  {
    $request->validate([
        'nomEntrepot' => 'required|string|max:255',
    ]);

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = null;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $entrepot = new Entrepot([
        'nomEntrepot' => $request->input('nomEntrepot'),
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    $entrepot->save();

    return response()->json(['message' => 'Entrepot ajouté avec succès', 'entrepot' => $entrepot]);
 }

public function listerEntrepots()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $notes = Entrepot::where('user_id', $userId)
        ->orWhere('sousUtilisateur_id', $sousUtilisateurId ?? 0)
        ->get();

    return response()->json($notes);
}

public function modifierEntrepot(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $entrepot = Entrepot::where('id', $id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();

        if ($entrepot) {
            $request->validate([
                'nomEntrepot' => 'required|string|max:255',
            ]);
            $user_id = null;
            $entrepot->nomEntrepot = $request->nomEntrepot;
            $entrepot->sousUtilisateur_id = $sousUtilisateurId; // Il n'est pas nécessaire de réassigner ces IDs
            $entrepot->user_id =$user_id; // Utiliser l'ID existant de l'utilisateur

            $entrepot->save();

            return response()->json(['message' => 'Entrepot modifié avec succès', 'Entrepot' => $entrepot]);
        } else {
            return response()->json(['error' => 'ce sous utilisateur n\'est pas autorisé à modifier ce entrepot'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $entrepot = Entrepot::where('id', $id)
            ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);})
            ->first();

        if ($entrepot) {
            $request->validate([
                'nomEntrepot' => 'required|string|max:255',
            ]);
            $sousUtilisateur_id = null;   
            $entrepot->nomEntrepot = $request->nomEntrepot;
            $entrepot->user_id = $userId; // Il n'est pas nécessaire de réassigner ces IDs
            $entrepot->sousUtilisateur_id = $sousUtilisateur_id; // Utiliser l'ID existant du sous-utilisateur

            $entrepot->save();

            return response()->json(['message' => 'Entrepot modifié avec succès', 'Entrepot' => $entrepot]);
        } else {
            return response()->json(['error' => ' ce utilisateur n\'est pas autorisé à modifier ce entrepot'], 401);
        }
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
  

public function supprimerEntrepot($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $entrepot = Entrepot::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($entrepot){
                $entrepot->delete();
            return response()->json(['message' => 'Entrepot supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $entrepot = Entrepot::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
            if ($entrepot) {
                $entrepot->delete();
                return response()->json(['message' => 'Entrepot supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

}  
}
