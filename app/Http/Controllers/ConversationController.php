<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{

    
    public function ajouterConversation(Request $request)
    {
        // Vérification des autorisations
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = $sousUtilisateur->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
        }
    
        // Validation des données
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:personnelle,email,message,telephone,courier_postal',
            'date_conversation' => 'required|date',
            'interlocuteur' => 'required|string|max:255',
            'objet' => 'required|string|max:255',
            'message_conversation' => 'required|string',
            'statut' => 'required|in:termine,en_attente',
            'client_id' => 'nullable|exists:clients,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Création de la conversation
        Conversation::create([
            'type' => $request->type,
            'date_conversation' => $request->date_conversation,
            'interlocuteur' => $request->interlocuteur,
            'objet' => $request->objet,
            'message_conversation' => $request->message_conversation,
            'statut' => $request->statut,
            'client_id' => $request->client_id,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    
        return response()->json(['message' => 'Conversation ajoutée avec succès.']);
    }


public function listerConversations()
{
    // Vérification des autorisations
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = $sousUtilisateur->id;
        $userId = $sousUtilisateur->id_user;

        $conversations = Conversation::with('client')
        ->where('sousUtilisateur_id', $sousUtilisateurId)
        ->orWhere('user_id', $userId)
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $conversations = Conversation::with('client')
        ->where('user_id', $userId)
        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
            $query->where('id_user', $userId);
        })
        ->get();   
     } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }


    return response()->json($conversations);
}


public function modifierConversation(Request $request, $id)
{
    // Vérification des autorisations
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Validation des données
        $validator = Validator::make($request->all(), [
        'type' => 'required|in:personnelle,email,message,telephone,courier_postal',
        'date_conversation' => 'required|date',
        'interlocuteur' => 'required|string|max:255',
        'objet' => 'required|string|max:255',
        'message_conversation' => 'required|string',
        'statut' => 'required|in:termine,en_attente',
        'client_id' => 'nullable|exists:clients,id',
    ]);
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Recherche de la conversation à modifier
    $conversation = Conversation::where('id', $id)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->firstOrFail();

    // Mise à jour de la conversation
    $conversation->update($request->all());

    return response()->json(['message' => 'Conversation modifiée avec succès.']);
}


public function supprimerConversation($id)
{
    // Vérification des autorisations
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Recherche et suppression de la conversation
    $conversation = Conversation::where('id', $id)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->firstOrFail();

    $conversation->delete();

    return response()->json(['message' => 'Conversation supprimée avec succès.']);
}


public function detailConversation($id)
{
    // Vérification des autorisations
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Récupération des détails de la conversation
    $conversation = Conversation::with('client')
        ->where('id', $id)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->firstOrFail();

    return response()->json($conversation);
}

}
