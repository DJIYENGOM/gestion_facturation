<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MessageNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{

    public function configurerNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'produit_rupture' => 'boolean',
            'depense_impayer' => 'boolean',
            'payement_attente' => 'boolean',
            'devis_expirer' => 'boolean',
            'relance_automatique' => 'boolean',
            'quantite_produit' => 'integer|min:1',
            'nombre_jourNotif_brouillon' => 'integer|min:1',
            'nombre_jourNotif_depense' => 'integer|min:1',
            'nombre_jourNotif_echeance' => 'integer|min:1',
            'nombre_jourNotif_devi' => 'integer|min:1',
            'recevoir_notification' => 'boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Récupération des données validées
        $validated = $validator->validated();
    
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
    
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            
            $sousUtilisateurId = $sousUtilisateur->id;
            $userId = $sousUtilisateur->id_user; 
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
        }
    
        $notification = Notification::where('user_id', $userId)
            ->orWhere('sousUtilisateur_id', $sousUtilisateurId)
            ->first();
    
        if ($notification) {
            $notification->update($validated);
        } else {
            // Création d'une nouvelle configuration avec les données validées
            $validated['user_id'] = $userId;
            $validated['sousUtilisateur_id'] = $sousUtilisateurId;
            $notification = Notification::create($validated);
        }
    
        return response()->json([
            'message' => 'Configuration des notifications mise à jour avec succès.',
            'data' => $notification,
        ]);
    }

    
    public function listerConfigurationNotification()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $notification = Notification::
            where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $notification = Notification::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    });
            })
            ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
    return response()->json([
        'message' => 'Configuration des notifications récupérée avec succès.',
        'data' => $notification,
    ]);
}

    public function listerNotifications()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

   
    $notificationsQuery = MessageNotification::select('message', DB::raw('count(*) as occurrences'))
        ->groupBy('message');

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
    $messageType = trim($request->input('message'));  // Supprimer les espaces avant et après

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    $notificationsQuery = MessageNotification::whereRaw('LOWER(message) = ?', [strtolower($messageType)]); //Comparaison insensible à la casse pour les majuscle/minuscle

if ($sousUtilisateurId) {
    $notificationsQuery->where('sousUtilisateur_id', $sousUtilisateurId);
} else {
    $notificationsQuery->where('user_id', $userId);
}

$notifications = $notificationsQuery->get();  

if ($notifications->isEmpty()) {
    return response()->json(['message' => "Aucun message trouvé pour ce type"], 404);
}

 $notificationsQuery->delete();  // Supprimer si les résultats existent

return response()->json(array('message' => "Message supprimé avec succès"));

}



}
