<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class PromoController extends Controller
{
 
public function ajouterPromo(Request $request)
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
            'nom_promo' => 'required|string|max:255',
            'pourcentage_promo' => 'required|numeric|min:0|max:100',
            'date_expiration' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Convertir le pourcentage en décimal (diviser par 100)
        $pourcentage_decimal = $request->pourcentage_promo / 100;

        $promo = Promo::create([
            'nom_promo' => $request->nom_promo,
            'pourcentage_promo' => $pourcentage_decimal,
            'date_expiration' => $request->date_expiration,
            'sousUtilisateur_id' => $sousUtilisateur_id,    
            'user_id' => $user_id,                               // ou  'sousUtilisateur_id' => Auth::id(),

        ]);

        return response()->json(['message' => 'Promo ajoutée avec succès', 'promo' => $promo]);
    }


    public function listerPromo()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $promos = Promo::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $promos = Promo::where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        foreach ($promos as $promo) {
            $pourcentage = number_format($promo->pourcentage_promo * 100, 0);
            $promo->pourcentage_promo = $pourcentage;
        }
    
        return response()->json(['promos' => $promos]);
    }
    

public function modifierPromo(Request $request, $id)
{
    $promo = Promo::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'nom_promo' => 'required|string|max:255',
        'pourcentage_promo' => 'required|numeric|min:0|max:100',
        'date_expiration' => 'required|date|after:today',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Convertir le pourcentage en décimal (diviser par 100)
    $pourcentage_decimal = $request->pourcentage_promo / 100;

    $promo->update([
        'nom_promo' => $request->nom_promo,
        'pourcentage_promo' => $pourcentage_decimal,
        'date_expiration' => $request->date_expiration,
    ]);

    // Retourner la réponse avec un message de succès et les données du promo mis à jour
    return response()->json(['message' => 'Promo modifiée avec succès', 'promo' => $promo]);
}


    public function supprimerPromo($id)
    {    
        $promo = Promo::findOrFail($id); // Rechercher la promo par son ID
        // Supprimer la promo
        $promo->delete();
    
        return response()->json(['message' => 'Promo supprimée avec succès']);
    }



}
