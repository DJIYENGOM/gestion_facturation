<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\CompteComptable;


class ClientController extends Controller
{
    public function ajouterClient(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $commonRules = [
            'type_client' => 'required|in:particulier,entreprise',
            'statut_client' => 'required|in:client,prospect',
            'categorie_id' => 'nullable|exists:categorie_clients,id',
            'num_client' => 'nullable|string|unique:clients,num_client',

        ];
    
        $particulierRules = [
            'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        ];
    
        $entrepriseRules = [
            'num_id_fiscal' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:50|min:2',

        ];
    
        $additionalRules = [
            'email_client' => 'required|email|max:255',
            'tel_client' => 'required|string|max:20|min:9',

        ];
    
        $rules = array_merge($commonRules, $additionalRules);
    
        if ($request->type_client == 'particulier') {
            $rules = array_merge($rules, $particulierRules);
        } elseif ($request->type_client == 'entreprise') {
            $rules = array_merge($rules, $entrepriseRules);
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        
        if (!$request->has('id_comptable')) {
                $compte = CompteComptable::where('nom_compte_comptable', 'Clients divers')
                                         ->first();
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        $client = new Client([
            'num_client' => $request->num_client,
            'nom_client' => $request->nom_client,
            'prenom_client' => $request->prenom_client,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_client' => $request->adress_client,
            'email_client' => $request->email_client,
            'tel_client' => $request->tel_client,
            'type_client' => $request->type_client,
            'statut_client' => $request->statut_client,
            'num_id_fiscal' => $request->num_id_fiscal,
            'code_postal_client' => $request->code_postal_client,
            'ville_client' => $request->ville_client,
            'pays_client' => $request->pays_client,
            'noteInterne_client' => $request->noteInterne_client,
            'nom_destinataire' => $request->nom_destinataire,
            'pays_livraison' => $request->pays_livraison,
            'ville_livraison' => $request->ville_livraison,
            'code_postal_livraison' => $request->code_postal_livraison,
            'tel_destinataire' => $request->tel_destinataire,
            'email_destinataire' => $request->email_destinataire,
            'infoSupplemnt' => $request->infoSupplemnt,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
            'categorie_id' => $request->categorie_id,
            'id_comptable' => $id_comptable,
        ]);
    
        $client->save();
    
        return response()->json(['message' => 'Client ajouté avec succès', 'client' => $client]);
    }
    

    public function listerClients()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $clients = Client::with('categorie','CompteComptable')
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $clients = Client::with('categorie','CompteComptable')
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $clientsArray = $clients->map(function ($client) {
            $clientArray = $client->toArray();
            $clientArray['nom_categorie'] = optional($client->categorie)->nom_categorie;
            $clientArray['nom_comptable'] = optional($client->CompteComptable)->nom_compte_comptable;
            return $clientArray;
        });
    
        return response()->json($clientsArray);
    }
    

public function modifierClient(Request $request, $id)
{
    $client = Client::findOrFail($id);

    // Déterminer l'utilisateur authentifié
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        if ($client->sousUtilisateur_id !== $sousUtilisateurId) {
            return response()->json(['error' => 'Cette sous-utilisateur ne peut pas modifier ce client car il ne l\'a pas créé'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();
        if ($client->user_id !== $userId) {
            return response()->json(['error' => 'Cet utilisateur ne peut pas modifier ce client car il ne l\'a pas créé'], 401);
        }
    } else {
        return response()->json(['error' => 'Non autorisé'], 401);
    }

    $commonRules = [
        'type_client' => 'required|in:particulier,entreprise',
        'statut_client' => 'required|in:client,prospect',
        'categorie_id' => 'nullable|exists:categorie_clients,id',
    ];

    $particulierRules = [
        'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    ];

    $entrepriseRules = [
        'num_id_fiscal' => 'required|string|max:255',
        'nom_entreprise' => 'required|string|max:50|min:2',

    ];

    $additionalRules = [
        'email_client' => 'required|email|max:255',
        'tel_client' => 'required|string|max:20|min:9',

    ];

    $rules = array_merge($commonRules, $additionalRules);

    if ($request->type_client == 'particulier') {
        $rules = array_merge($rules, $particulierRules);
    } elseif ($request->type_client == 'entreprise') {
        $rules = array_merge($rules, $entrepriseRules);
    }

    // Valider les données reçues
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Mettre à jour les données du client
    $client->update($request->all());

    return response()->json(['message' => 'Client modifié avec succès', 'client' => $client]);
}


public function supprimerClient($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $client = Client::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($client){
                $client->delete();
            return response()->json(['message' => 'client supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce client'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $client = Client::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            if($client){
                $client->delete();
                return response()->json(['message' => 'client supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce client'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}
}
