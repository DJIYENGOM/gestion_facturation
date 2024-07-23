<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NumeroGeneratorService;
use App\Models\Echeance;


class DepenseController extends Controller
{
    public function creerDepense(Request $request)
{
    // Valider les données entrantes
    $validator = Validator::make($request->all(), [
        'activation' => 'boolean',
        'commentaire' => 'nullable|string',
        'date_paiement' => 'required|date',
        'tva_depense' => 'nullable|integer',
        'montant_depense_ht' => 'required|numeric',
        'montant_depense_ttc' => 'required|numeric',
        'plusieurs_paiement' => 'required|boolean',
        'duree_indeterminee' => 'required|boolean',
        'periode_echeance' => 'required_if:plusieurs_paiement,true,duree_indeterminee,true|in:jour,mois,semaine',
        'nombre_periode' => 'required_if:plusieurs_paiement,true,duree_indeterminee,true|integer',

        'echeances' => 'nullable|required_if:plusieur_paiement,true|array',
        'echeances.*.date_pay_echeance' => 'required|date',
        'echeances.*.montant_echeance' => 'required|numeric|min:0',

        'doc_externe' => 'nullable|string|max:255',
        'num_facture' => 'nullable|string|max:255',
        'date_facture' => 'nullable|date',
        'statut_depense' => 'required|in:payer,impayer',
        'id_paiement' => 'nullable|exists:payements,id',
        'fournisseur_id' => 'nullable|exists:fournisseurs,id',
        'id_categorie_depense' => 'required|exists:categorie_depenses,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Déterminer l'utilisateur ou le sous-utilisateur connecté
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    $typeDocument = 'depense';
    $numdepense = NumeroGeneratorService::genererNumero($userId, $typeDocument);


    // Créer la dépense
    $depense = Depense::create([
        'num_depense' => $numdepense,
        'activation' => $request->input('activation', true),
        'commentaire' => $request->input('commentaire'),
        'date_paiement' => $request->date_paiement,
        'tva_depense' => $request->tva_depense,
        'montant_depense_ht' => $request->montant_depense_ht,
        'montant_depense_ttc' => $request->montant_depense_ttc,
        'plusieurs_paiement' => $request->plusieurs_paiement,
        'duree_indeterminee' => $request->duree_indeterminee,
        'periode_echeance' => $request->periode_echeance,
        'nombre_periode' => $request->nombre_periode,
        'doc_externe' => $request->doc_externe,
        'num_facture' => $request->num_facture,
        'date_facture' => $request->date_facture,
        'statut_depense' => $request->statut_depense,
        'id_paiement' => $request->id_paiement,
        'fournisseur_id' => $request->fournisseur_id,
        'id_categorie_depense' => $request->id_categorie_depense,
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);


    if ($request->plusieur_paiement == true) {
        foreach ($request->echeances as $echeanceData) {
            Depense::create([
                'num_depense' => $numdepense,
                'activation' => $request->input('activation', true),
                'commentaire' => $request->input('commentaire'),
                'date_paiement' => $echeanceData['date_pay_echeance'],
                'montant_depense_ht' => $echeanceData['montant_echeance'],
                'plusieurs_paiement' => $request->plusieurs_paiement,
                'duree_indeterminee' => $request->duree_indeterminee,
                'periode_echeance' => $request->periode_echeance,
                'nombre_periode' => $request->nombre_periode,
                'doc_externe' => $request->doc_externe,
                'num_facture' => $request->num_facture,
                'date_facture' => $request->date_facture,
                'statut_depense' => 'impayer',
                'fournisseur_id' => $request->fournisseur_id,
                'id_categorie_depense' => $request->id_categorie_depense,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
    }
    return response()->json(['message' => 'Dépense créée avec succès', 'depense' => $depense], 201);
}

    
}
