<?php

namespace App\Http\Controllers;

use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\Echeance;

class PaiementRecuController extends Controller
{
    public function ajouterPaiementRecu(Request $request)
    {
        $request->validate([
            'facture_id' => 'required|exists:factures,id',
            'num_paiement' => 'nullable|string|max:255',
            'date_prevu' => 'nullable|date',
            'date_recu' => 'nullable|date',
            'montant' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
            'id_paiement' => 'required|exists:payements,id',
        ]);

        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $paiementRecu = PaiementRecu::create([
            'facture_id' => $request->facture_id,
            'num_paiement' => $request->input('num_paiement'),
            'date_prevu' => $request->input('date_prevu'),
            'date_recu' => $request->input('date_recu'),
            'montant' => $request->input('montant'),
            'commentaire' => $request->input('commentaire'),
            'id_paiement' => $request->input('id_paiement'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);

        return response()->json(['message' => 'Paiement recu ajouté avec succès', 'paiement_recu' => $paiementRecu], 201);
    }

    public function listPaiementsRecusParFacture($factureId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;

            $paiementsRecus = PaiementRecu::where('facture_id', $factureId)
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();

            $paiementsRecus = PaiementRecu::where('facture_id', $factureId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateur', function($subQuery) use ($userId) {
                              $subQuery->where('id_user', $userId);
                          });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['paiements_recus' => $paiementsRecus], 200);
    }

    public function supprimerPaiementRecu($paiementRecuId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;

            $paiementRecu = PaiementRecu::where('id', $paiementRecuId)
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->first();

            if ($paiementRecu) {
                $paiementRecu->delete();
                return response()->json(['message' => 'Paiement recu supprimé avec succès'], 200);
            } else {
                return response()->json(['error' => 'Ce sous-utilisateur ne peut pas supprimer ce paiement recu'], 403);
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();

            $paiementRecu = PaiementRecu::where('id', $paiementRecuId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateur', function($subQuery) use ($userId) {
                              $subQuery->where('id_user', $userId);
                          });
                })
                ->first();

            if ($paiementRecu) {
                $paiementRecu->delete();
                return response()->json(['message' => 'Paiement recu supprimé avec succès'], 200);
            } else {
                return response()->json(['error' => 'Cet utilisateur ne peut pas supprimer ce paiement recu'], 403);
            }
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function transformerPaiementRecuEnEcheance($paiementRecuId)
{
    // Vérifiez les permissions de l'utilisateur
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Récupérer le paiement recu
    $paiementRecu = PaiementRecu::find($paiementRecuId);
    if (!$paiementRecu) {
        return response()->json(['error' => 'Paiement recu non trouvé'], 404);
    }

    // Créez une nouvelle échéance à partir des informations du paiement recu
    $echeance = Echeance::create([
        'facture_id' => $paiementRecu->facture_id,
        'date_pay_echeance' => $paiementRecu->date_prevu,
        'montant_echeance' => $paiementRecu->montant,
        'commentaire' => null,
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    // Supprimez le paiement recu
    $paiementRecu->delete();

    return response()->json([
        'message' => 'Paiement recu transformé en échéance avec succès',
        'echeance' => $echeance
    ], 201);
}

}
