<?php

namespace App\Http\Controllers;

use App\Models\Historique;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoriqueController extends Controller
{

public function listerMessagesHistoriqueAujourdhui()
{
    // Vérifier si l'utilisateur est un sous-utilisateur ou un utilisateur standard
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Obtenir les messages d'historique créés aujourd'hui pour l'utilisateur ou sous-utilisateur connecté
    $today = Carbon::today();
    
    $historiquesQuery = Historique::whereDate('created_at', $today)
        ->select('message', DB::raw('count(*) as occurrences'))
        ->groupBy('message')
        ->orderBy('occurrences', 'desc');

    // Filtrer par utilisateur ou sous-utilisateur
    if ($sousUtilisateurId) {
        $historiquesQuery->where('sousUtilisateur_id', $sousUtilisateurId)
                         ->orWhere('user_id', $userId);
    } else {
        $historiquesQuery->where('user_id', $userId);
    }

    $historiques = $historiquesQuery->get();

    return response()->json($historiques);
}

public function supprimerHistorique($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $Historique = Historique::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
        if ($Historique)
            {
                $Historique->delete();
            return response()->json(['message' => 'Historique supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet Historique'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $Historique = Historique::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();

            if ($Historique)
            {
                $Historique->delete();
                return response()->json(['message' => 'Historique supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet Historique'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}
}
