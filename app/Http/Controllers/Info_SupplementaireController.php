<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class Info_SupplementaireController extends Controller
{
    public function completerInfoEntreprise(Request $request)
{
    if (auth()->check()) {
        // Valider les données envoyées par l'utilisateur
        $validator = Validator::make($request->all(), [
            'nom_entreprise' => 'required|string|unique:users,nom_entreprise,' . auth()->id(),
            'description_entreprise' => 'nullable|string',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'adress_entreprise' => 'nullable|string',
            'tel_entreprise' => 'nullable|string',
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
    if (auth()->check()) {
        $user = auth()->user();

        // Récupérer l'URL du logo si disponible
        $logoUrl = $user->logo ? asset('storage/' . $user->logo) : null;

        return response()->json([
            'user' => [
                'nom_entreprise' => $user->nom_entreprise,
                'description_entreprise' => $user->description_entreprise,
                'logo' => $logoUrl, 
                'adress_entreprise' => $user->adress_entreprise,
                'tel_entreprise' => $user->tel_entreprise,
            ]
        ]);
    }
    return response()->json(['error' => 'Unauthorized'], 401);
}

}
