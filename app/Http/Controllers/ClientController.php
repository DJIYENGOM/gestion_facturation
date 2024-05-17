<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;

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
        $validator=Validator::make($request->all(),[
            'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'nom_entreprise' => 'required|string|max:255',
            'adress_client' => 'required|string|max:255',
            'email_client' => 'required|email|max:255',
            'tel_client' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categorie_clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
    
        $client = new Client([
            'nom_client' => $request->nom_client,
            'prenom_client' => $request->prenom_client,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_client' => $request->adress_client,
            'email_client' => $request->email_client,
            'tel_client' => $request->tel_client,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'categorie_id' => $request->categorie_id,
            'user_id' => $user_id,
        ]);
    
        $client->save();
    
        return response()->json(['message' => 'Client ajouté avec succès', 'client' => $client]);
    }


public function listerClients()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $clients = Client::with('categorie')
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $clients = Client::with('categorie')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $clients = $clients->map(function ($client) {
        return [
            'id' => $client->id,
            'nom_client' => $client->nom_client,
            'prenom_client' => $client->prenom_client,
            'email_client' => $client->email_client,
            'nom_entreprise' => $client->nom_entreprise,
            'adress_client' => $client->adress_client,
            'tel_client' => $client->tel_client,
            'user_id' => $client->user_id,
            'sousUtilisateur_id' => $client->sousUtilisateur_id,
            'created_at' => $client->created_at,
            'updated_at' => $client->updated_at,
            'nom_categorie' => $client->categorie->nom_categorie,
        ];
    });

    return response()->json($clients);
}

public function modifierClient(Request $request, $id)
{
    $client = Client::findOrFail($id);

    // Valider les données reçues
    $validator = Validator::make($request->all(), [
        'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'nom_entreprise' => 'required|string|max:255',
        'adress_client' => 'required|string|max:255',
        'email_client' => 'required|email|max:255',
        'tel_client' => 'required|string|max:255',
        'categorie_id' => 'required|exists:categorie_clients,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $client->update($request->all());

    return response()->json(['message' => 'Client modifié avec succès', 'client' => $client]);
}

public function supprimerClient($id)
{
    $client = Client::findOrFail($id);
    $client->delete();

    return response()->json(['message' => 'Client supprimé avec succès']);
}
}
