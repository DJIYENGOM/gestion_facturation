<?php

namespace App\Http\Controllers;

use App\Models\Payement;
use App\Http\Requests\StorePayementRequest;
use App\Http\Requests\UpdatePayementRequest;
use Illuminate\Http\Request;

class PayementController extends Controller
{
   

  public function ajouterPayement(Request $request)
  {
    $request->validate([
        'nom_payement' => 'required|string|max:255',
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

    $payement = new Payement([
        'nom_payement' => $request->input('nom_payement'),
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    $payement->save();

    return response()->json(['message' => 'Payement ajouté avec succès', 'payement' => $payement]);
 }

public function listerPayements()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $notes = Payement::where('user_id', $userId)
        ->orWhere('sousUtilisateur_id', $sousUtilisateurId ?? 0)
        ->get();

    return response()->json($notes);
}

public function modifierPayement(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $payement = Payement::where('id', $id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();

        if ($payement) {
            $request->validate([
                'nom_payement' => 'required|string|max:255',
            ]);
            $user_id = null;
            $payement->nom_payement = $request->nom_payement;
            $payement->sousUtilisateur_id = $sousUtilisateurId; // Il n'est pas nécessaire de réassigner ces IDs
            $payement->user_id =$user_id; // Utiliser l'ID existant de l'utilisateur

            $payement->save();

            return response()->json(['message' => 'Payement modifié avec succès', 'Payement' => $payement]);
        } else {
            return response()->json(['error' => 'ce sous utilisateur n\'est pas autorisé à modifier ce payement'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $payement = Payement::where('id', $id)
            ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);})
            ->first();

        if ($payement) {
            $request->validate([
                'nom_payement' => 'required|string|max:255',
            ]);
            $sousUtilisateur_id = null;   
            $payement->nom_payement = $request->nom_payement;
            $payement->user_id = $userId; // Il n'est pas nécessaire de réassigner ces IDs
            $payement->sousUtilisateur_id = $sousUtilisateur_id; // Utiliser l'ID existant du sous-utilisateur

            $payement->save();

            return response()->json(['message' => 'Payement modifié avec succès', 'Payement' => $payement]);
        } else {
            return response()->json(['error' => ' ce utilisateur n\'est pas autorisé à modifier ce payement'], 401);
        }
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
  

public function supprimerPayement($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       if( $payement = Payement::findOrFail($id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->delete()){

            return response()->json(['message' => 'Payement supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        if($payement = Payement::findOrFail($id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->delete()){
                return response()->json(['message' => 'Payement supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

}    
}
