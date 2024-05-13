<?php

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class PromoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function ajouterPromo(Request $request)
    {
        $user = auth('apisousUtilisateur')->user();

        $request->validate([
            'nom_promo' => 'required|string|max:255',
            'pourcentage_promo' => 'required|numeric|min:0|max:100',
            'date_expiration' => 'required|date|after:today',
        ]);
          // Convertir le pourcentage en décimal (diviser par 100)
          $pourcentage_decimal = $request->pourcentage_promo / 100;

        $promo = Promo::create([
            'nom_promo' => $request->nom_promo,
            'pourcentage_promo' => $pourcentage_decimal,
            'date_expiration' => $request->date_expiration,
            'sousUtilisateur_id' => $user->id,              // ou  'sousUtilisateur_id' => Auth::id(),

        ]);
    
        return response()->json(['message' => 'Promo ajoutée avec succès', 'promo' => $promo]);
    }


    public function supprimerPromo($id)
    {    
        $promo = Promo::findOrFail($id); // Rechercher la promo par son ID
        // Supprimer la promo
        $promo->delete();
    
        return response()->json(['message' => 'Promo supprimée avec succès']);
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePromoRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Promo $promo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Promo $promo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePromoRequest $request, Promo $promo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Promo $promo)
    {
        //
    }
}
