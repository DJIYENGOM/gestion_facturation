<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Info_SupplementaireController extends Controller
{
    public function completerInfoEntreprise(Request $request)
{
    if (auth()->check()) {
        // Valider les données envoyées par l'utilisateur
        $validator = Validator::make($request->all(), [
            'nom_entreprise' => ['required', 'string', 'min:2', 'max:255','regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'description_entreprise' => 'nullable|string|max:255',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'adress_entreprise' => 'nullable|string|max:255|min:2,',
            'tel_entreprise' => 'nullable|string|max:20|min:9',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $logo=null;
        if($request->hasFile('logo')){
            $logo=$request->file('logo')->store('images', 'public');
             }
        $user = auth()->user();

        $user->update([
            'nom_entreprise' => $request->nom_entreprise,
            'description_entreprise' => $request->description_entreprise,
            'logo' => $logo,
            'adress_entreprise' => $request->adress_entreprise,
            'tel_entreprise' => $request->tel_entreprise,
        ]);

        return response()->json(['message' => 'Profil mis à jour avec succès', 'user' => $user]);
    }
    return response()->json(['error' => 'Unauthorized'], 401);
}


public function afficherInfoEntreprise()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Récupérer l'utilisateur principal ou le sous-utilisateur lié
    $user = User::where('id', $userId)
        ->orWhereHas('Sous_utilisateur', function ($query) use ($userId) {
            $query->where('id_user', $userId);
        })
        ->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $logoUrl = $user->logo ? asset('storage/' . $user->logo) : null;

    return response()->json([
        'user' => [
            'id' => $user->id,
            'nom_entreprise' => $user->nom_entreprise,
            'description_entreprise' => $user->description_entreprise,
            'logo' => $logoUrl,
            'adresse_entreprise' => $user->adresse_entreprise,
            'tel_entreprise' => $user->tel_entreprise,
        ]
    ]);
}



}
