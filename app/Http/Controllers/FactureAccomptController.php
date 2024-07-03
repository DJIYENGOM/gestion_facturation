<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use App\Models\FactureAccompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class FactureAccomptController extends Controller
{

public function creerFactureAccomp(Request $request)
{
    // Validation des données d'entrée
    $validator = Validator::make($request->all(), [
        'facture_id' => 'nullable|exists:factures,id',
        'devi_id' => 'nullable|exists:devis,id',
        'titreAccomp' => 'required|string|max:255',
        'dateAccompt' => 'required|date',
        'dateEcheance' => 'required|date',
        'montant' => 'required|numeric|min:0',
        'commentaire' => 'nullable|string',
    ]);

    // Vérification des erreurs de validation
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Récupération de l'utilisateur authentifié
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Création de la facture d'acompte
    $factureAccomp = FactureAccompt::create([
        'facture_id' => $request->facture_id,
        'devi_id' => $request->devi_id,
        'titreAccomp' => $request->titreAccomp,
        'dateAccompt' => $request->dateAccompt,
        'dateEcheance' => $request->dateEcheance,
        'montant' => $request->montant,
        'commentaire' => $request->input('commentaire', ''),
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);
    
    Echeance::create([
        'facture_id' => $factureAccomp->facture_id,
        'devi_id' => $factureAccomp->devi_id,
        'date_pay_echeance' => $factureAccomp->dateEcheance,
        'montant_echeance' => $factureAccomp->montant,
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    return response()->json(['message' => 'Facture d\'acompte créée avec succès', 'factureAccomp' => $factureAccomp], 201);
}

public function listerfactureAccomptsParFacture($id_facture)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $factures = FactureAccompt::with('facture')
            ->where('facture_id', $id_facture)
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = FactureAccompt::with('facture')
            ->where('facture_id', $id_facture)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
// Construire la réponse avec les détails des factures et les noms des clients
$response = [];
foreach ($factures as $facture) {
    $response[] = [
        'id' => $facture->id,
        'facture_id' => $facture->facture_id,
        'devi_id' => $facture->devi_id,
        'titreAccomp' => $facture->titreAccomp,
        'dateAccompt' => $facture->dateAccompt,
        'dateEcheance' => $facture->dateEcheance,
        'montant' => $facture->montant,
        'commentaire' => $facture->commentaire,
        'prenom client' => $facture->facture->client->prenom_client, 
        'nom client' => $facture->facture->client->nom_client,
        'num facture' => $facture->facture->num_fact,
    ];
}

return response()->json(['factures' => $response]);
}

}
