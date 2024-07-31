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
            'num_depense' => 'nullable|string',
            'activation' => 'boolean',
            'id_categorie_depense' => 'required|exists:categorie_depenses,id',
            'commentaire' => 'nullable|string',
            'date_paiement' => 'nullable|date',
            'tva_depense' => 'nullable|integer',
            'montant_depense_ht' => 'nullable|numeric',
            'montant_depense_ttc' => 'nullable|numeric',
            'plusieurs_paiement' => 'nullable|boolean',
            'duree_indeterminee' => 'nullable|boolean',
            'periode_echeance' =>' nullable|in:jour,mois,semaine',
            'nombre_periode' => 'nullable|integer',
    
            'echeances' => 'nullable|required_if:plusieurs_paiement,true|array',
            'echeances.*.date_pay_echeance' => 'nullable|date',
            'echeances.*.montant_echeance' => 'nullable|numeric|min:0',
    
            'doc_externe' => 'nullable|string|max:255',
            'num_facture' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'statut_depense' => 'required|in:payer,impayer',
            'id_paiement' => 'nullable|required_if:statut_depense,payer|exists:payements,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
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
    
        
        if ($request->plusieurs_paiement == true) {
            foreach ($request->echeances as $index => $echeanceData) {
        
                $depense = Depense::create([
                    'num_depense' => $request->num_depense. '-' .($index + 1),
                    'activation' => $request->input('activation', true),
                    'commentaire' => $request->input('commentaire'),
                    'date_paiement' => $echeanceData['date_pay_echeance'] ?? $request->date_paiement,
                    'montant_depense_ht' => $echeanceData['montant_echeance'] ?? $request->montant_depense_ht,
                    'montant_depense_ttc' => $echeanceData['montant_echeance'] ?? $request->montant_depense_ttc,
                    'plusieurs_paiement' => $request->plusieurs_paiement,
                    'duree_indeterminee' => $request->duree_indeterminee ?? false,
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
        } else {
            $typeDocument = 'depense';
        $numdepense = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
            $depense = Depense::create([
                'num_depense' =>  $request->num_depense ?? $numdepense,
                'activation' => $request->input('activation', true),
                'id_categorie_depense' => $request->id_categorie_depense,
                'commentaire' => $request->input('commentaire'),
                'date_paiement' => $request->date_paiement,
                'tva_depense' => $request->tva_depense,
                'montant_depense_ht' => $request->montant_depense_ht,
                'montant_depense_ttc' => $request->montant_depense_ttc,
                'plusieurs_paiement' => $request->plusieurs_paiement ?? false,
                'duree_indeterminee' => $request->duree_indeterminee ?? false,
                'periode_echeance' => $request->periode_echeance ?? null,
                'nombre_periode' => $request->nombre_periode ?? null,
                'doc_externe' => $request->doc_externe,
                'num_facture' => $request->num_facture,
                'date_facture' => $request->date_facture,
                'statut_depense' => $request->statut_depense ?? 'impayer',
                'id_paiement' => $request->id_paiement,
                'fournisseur_id' => $request->fournisseur_id,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
    
        return response()->json(['message' => 'Dépense créée avec succès', 'depense' => $depense], 201);
    }
    
    public function listerDepenses()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $depenses = Depense::with('categorieDepense', 'fournisseur')
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $depenses = Depense::with('categorieDepense', 'fournisseur')
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $response = $depenses->map(function ($depense) {
            return [
                'num_depense' => $depense->num_depense,
                'id' => $depense->id,
                'activation' => $depense->activation,
                'commentaire' => $depense->commentaire,
                'date_paiement' => $depense->date_paiement,
                'tva_depense' => $depense->tva_depense,
                'montant_depense_ht' => $depense->montant_depense_ht,
                'montant_depense_ttc' => $depense->montant_depense_ttc,
                'plusieurs_paiement' => $depense->plusieurs_paiement,
                'duree_indeterminee' => $depense->duree_indeterminee,
                'periode_echeance' => $depense->periode_echeance,
                'nombre_periode' => $depense->nombre_periode,
                'doc_externe' => $depense->doc_externe,
                'num_facture' => $depense->num_facture,
                'date_facture' => $depense->date_facture,
                'statut_depense' => $depense->statut_depense,
                'id_paiement' => $depense->id_paiement,
                'fournisseur_id' => $depense->fournisseur_id,
                'categorie_depense_id' => $depense->id_categorie_depense,
                'nom_categorie_depense' => $depense->categorieDepense ? $depense->categorieDepense->nom_categorie_depense : null,
                'nom_fournisseur' => $depense->fournisseur ? $depense->fournisseur->nom : null,
                'prenom_fournisseur' => $depense->fournisseur ? $depense->fournisseur->prenom : null,
            ];
        });
    
        return response()->json(['depenses' => $response], 200);
    }
    

    
    public function modifierDepense(Request $request, $id)
    {
        // Valider les données entrantes
        $validator = Validator::make($request->all(), [
            'num_depense' => 'nullable|string|max:255',
            'activation' => 'boolean',
            'id_categorie_depense' => 'required|exists:categorie_depenses,id',
            'commentaire' => 'nullable|string',
            'date_paiement' => 'nullable|date',
            'tva_depense' => 'nullable|integer',
            'montant_depense_ht' => 'nullable|numeric',
            'montant_depense_ttc' => 'nullable|numeric',
            'plusieurs_paiement' => 'nullable|boolean',
            'duree_indeterminee' => 'nullable|boolean',
            'periode_echeance' => 'required_if:duree_indeterminee,true|in:jour,mois,semaine',
            'nombre_periode' => 'required_if:duree_indeterminee,true|integer',
            'doc_externe' => 'nullable|string|max:255',
            'num_facture' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'statut_depense' => 'required|in:payer,impayer',
            'id_paiement' => 'nullable|required_if:statut_depense,payer|exists:payements,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
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
    
        // Récupérer la dépense à modifier
        $depense = Depense::where('id', $id)
                    ->where(function ($query) use ($userId, $sousUtilisateurId) {
                        $query->where('user_id', $userId)
                              ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                    })
                    ->first();
    
        if (!$depense) {
            return response()->json(['error' => 'Dépense non trouvée'], 404);
        }
    
        // Mettre à jour la dépense
        $depense->update($request->all());
    
        return response()->json(['message' => 'Dépense modifiée avec succès', 'depense' => $depense], 200);
    }
    
    public function supprimerDepense($id)
{
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

    // Récupérer la dépense à supprimer
    $depense = Depense::where('id', $id)
                ->where(function ($query) use ($userId, $sousUtilisateurId) {
                    $query->where('user_id', $userId)
                          ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                })
                ->first();

    if (!$depense) {
        return response()->json(['error' => 'Dépense non trouvée'], 404);
    }

    // Supprimer la dépense
    $depense->delete();

    return response()->json(['message' => 'Dépense supprimée avec succès'], 200);
}

    
}
