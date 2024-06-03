<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use App\Models\CompteComptable;
use Illuminate\Http\Request;

class CompteComptableController extends Controller
{
    public function ajouterCompteComptable(Request $request)
{
    if (auth()->check()) {
        $user_id = auth()->id();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
        'nom_compte_comptable' => 'required|string|max:255',
        'code_compte_comptable' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $compteComptable = CompteComptable::create([
        'nom_compte_comptable' => $request->nom_compte_comptable,
        'code_compte_comptable' => $request->code_compte_comptable,
        'user_id' => $user_id,
    ]);

    return response()->json(['message' => 'Compte comptable ajouté avec succès', 'compteComptable' => $compteComptable]);
}

public function listerCompteComptables()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $comptes = CompteComptable::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $comptes = CompteComptable::where('user_id', $userId)
            ->orWhereHas('sousUtilisateurs', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return response()->json(['comptes' => $comptes]);
} 

public function modifierCompteComptable(Request $request, $id)
{
    $compte = CompteComptable::findOrFail($id);

    // Vérifier si l'utilisateur est autorisé à modifier ce compte
    if ($compte->user_id !== auth()->id()) {
        return response()->json(['error' => 'Vous n\'êtes pas autorisé à modifier ce compte'], 403);
    }

    $validator = Validator::make($request->all(), [
        'nom_compte_comptable' => 'required|string|max:255',
        'code_compte_comptable' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $compte->nom_compte_comptable = $request->nom_compte_comptable;
    $compte->code_compte_comptable = $request->code_compte_comptable;
    $compte->save();

    return response()->json(['message' => 'Compte comptable modifié avec succès', 'compte' => $compte]);
}

}
