<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TvaController extends Controller
{
    public function InfoSurTva_Recolte_Deductif_Reverse()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

            // Requête pour obtenir les totaux pour le sous-utilisateur et l'utilisateur parent
            $tvas = DB::table('tvas')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();

            // Requête pour obtenir les totaux pour l'utilisateur principal et ses sous-utilisateurs
            $tvas = DB::table('tvas')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhere('sousUtilisateur_id', $userId);
                })
                ->get();

        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        $totalTvaRecolte = $tvas->sum('tva_recolte');
        $totalTvaDeductif = $tvas->sum('tva_deductif');
        $totalTvaReverse = $totalTvaRecolte - $totalTvaDeductif;

        // Préparation de la réponse
        $response = [
            'total_tva_recolte' => $totalTvaRecolte,
            'total_tva_deductif' => $totalTvaDeductif,
            'total_tva_reverse' => $totalTvaReverse,
        ];

        return response()->json($response, 200);
    }
}
