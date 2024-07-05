<?php

namespace App\Http\Controllers;

use App\Models\NumeroConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NumeroConfigurationController extends Controller
{
    public function configurerNumeros(Request $request)
    {
        // Vérifier si l'utilisateur est connecté
        if (auth()->check()) {
            $userId = auth()->id();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Valider les données envoyées
        $validator = Validator::make($request->all(), [
            'type_document' => 'required|in:facture,livraison,produit,service,client,devis,commande,depense,fournisseur,commande_achat',
            'type_numerotation' => 'required|in:par_defaut,avec_prefixe',
            'prefixe' => 'nullable|required_if:type_numerotation,avec_prefixe|string|max:10',
            'format' => 'nullable|required_if:type_numerotation,avec_prefixe|in:annee,annee_mois,annee_mois_jour',
        ]);

        // En cas d'échec de validation, retourner les erreurs
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mettre à jour ou créer la configuration de numérotation
        $configuration = NumeroConfiguration::updateOrCreate(
            [
                'user_id' => $userId,
                'type_document' => $request->type_document,
            ],
            [
                'type_numerotation' => $request->type_numerotation,
                'prefixe' => $request->prefixe,
                'format' => $request->format,
                'compteur' => 0, // Réinitialiser le compteur à 0 lors de la configuration
            ]
        );

        return response()->json(['message' => 'Configuration de numérotation mise à jour avec succès', 'configuration' => $configuration]);
    }
}
