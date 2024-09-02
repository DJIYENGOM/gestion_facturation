<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function listerNotifications()
{
    // Vérifier si l'utilisateur est un sous-utilisateur ou un utilisateur standard
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

   
    $notificationsQuery = Notification::select('message', DB::raw('count(*) as occurrences'))
        ->groupBy('message')
        ->orderBy('created_at', 'desc');

    // Filtrer par utilisateur ou sous-utilisateur
    if ($sousUtilisateurId) {
        $notificationsQuery->where('sousUtilisateur_id', $sousUtilisateurId);
        
    } else {
        $notificationsQuery->where('user_id', $userId);
    }

    $notifications = $notificationsQuery->get();

    return response()->json($notifications);
}

public function supprimeNotificationParType(Request $request)
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
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Suppression des messages en fonction du type et de l'utilisateur
    $notificationsQuery = Notification::where('message', $messageType);

    if ($sousUtilisateurId) {
        $notificationsQuery->where('sousUtilisateur_id', $sousUtilisateurId);
    } else {
        $notificationsQuery->where('user_id', $userId);
    }

    $deleted = $notificationsQuery->delete();

    return response()->json(['deleted' => $deleted, 'message' => "Messages de type '{$messageType}' supprimés"], 200);
}

}
