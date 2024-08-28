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
        $historiquesQuery->where('sousUtilisateur_id', $sousUtilisateurId);
        
    } else {
        $historiquesQuery->where('user_id', $userId);
    }

    $historiques = $historiquesQuery->get();

    return response()->json($historiques);
}


    public function supprimerMessagesParType(Request $request)
    {
        $messageType = $request->input('message'); // Type de message à supprimer

        // Vérification de l'utilisateur connecté
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Suppression des messages en fonction du type et de l'utilisateur
        $historiquesQuery = Historique::where('message', $messageType);

        if ($sousUtilisateurId) {
            $historiquesQuery->where('sousUtilisateur_id', $sousUtilisateurId);
        } else {
            $historiquesQuery->where('user_id', $userId);
        }

        $deleted = $historiquesQuery->delete();

        return response()->json(['deleted' => $deleted, 'message' => "Messages de type '{$messageType}' supprimés"], 200);
    }
}
