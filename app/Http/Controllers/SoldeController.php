<?php

namespace App\Http\Controllers;

use App\Models\Solde;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SoldeController extends Controller
{

    public function ajouterSolde(Request $request, $clientId)
    {
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
    
        // Vérifier si le client a déjà un solde
        $soldeExistant = Solde::where('client_id', $clientId)->first();
    
        if ($soldeExistant) {
            $soldeExistant->montant += $request->input('montant');
            $soldeExistant->commentaire = $request->input('commentaire') ?: $soldeExistant->commentaire;
            $soldeExistant->date_paiement = $request->input('date_paiement') ?: $soldeExistant->date_paiement;
            $soldeExistant->id_paiement = $request->input('id_paiement') ?: $soldeExistant->id_paiement;
            $soldeExistant->facture_id = $request->input('facture_id') ?: $soldeExistant->facture_id;
            
            // Sauvegarder les modifications
            $soldeExistant->save();
    
            return response()->json(['message' => 'Solde mis à jour avec succès', 'solde' => $soldeExistant]);
        } else {
            // Si aucun solde n'existe, créer un nouveau solde
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
    
            return response()->json(['message' => 'Solde ajouté avec succès', 'solde' => $solde]);
        }
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
                ->where('archiver', 'non')
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
                ->where('archiver', 'non')
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
    return response()->json(['soldes' => $soldes]);
    }

    public function supprimer_archiverSolde($id)
    {
        // Déterminer l'utilisateur ou le sous-utilisateur connecté
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
                if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                    return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
                }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $Solde = Solde::where('id', $id)
                    ->where(function ($query) use ($userId, $sousUtilisateurId) {
                        $query->where('user_id', $userId)
                              ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                    })
                    ->first();
    
        if (!$Solde) {
            return response()->json(['error' => 'Solde non trouvée'], 404);
        }
    
        $Solde->archiver='oui';
        $Solde->save();
        Artisan::call(command: 'optimize:clear');
        return response()->json(['message' => 'Solde supprimée avec succès'], 200);
    }
}
