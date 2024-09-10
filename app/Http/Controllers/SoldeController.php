<?php

namespace App\Http\Controllers;

use App\Models\Solde;
use Illuminate\Http\Request;

class SoldeController extends Controller
{

    public function ajouterSolde(Request $request, $clientId){
        $request->validate([
            'montant' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
            'date_paiement' => 'required|date',
            'id_paiement' => 'nullable|exists:payements,id',
            'facture_id'=> 'nullable|exists:factures,id',
        ]);
    
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = null;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $solde = new Solde([
            'montant' => $request->input('montant'),
            'commentaire' => $request->input('commentaire'),
            'date_paiement' => $request->input('date_paiement'),
            'id_paiement' => $request->input('id_paiement'),
            'facture_id' => $request->input('facture_id'),
            'client_id' => $clientId,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    
        $solde->save();
    
        return response()->json(['message' => 'solde ajouté avec succès', 'solde' => $solde]);
     }

    public function listeSoldeParClient($clientId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
              return response()->json(['error' => 'Accès non autorisé'], 403);
              }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $soldes = Solde::where('client_id', $clientId)
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $soldes = Solde::where('client_id', $clientId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
    return response()->json(['soldes' => $soldes]);
    }
}
