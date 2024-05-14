<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;

class ClientController extends Controller
{
    public function ajouterClient(Request $request)
    {
        $request->validate([
            'nom_client' => 'required|string|max:255',
            'prenom_client' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:255',
            'adress_client' => 'required|string|max:255',
            'email_client' => 'required|email|max:255',
            'tel_client' => 'required|string|max:255',
            'categorie_id' => 'required|exists:categorie_clients,id',
        ]);
    
        $client = new Client([
            'nom_client' => $request->nom_client,
            'prenom_client' => $request->prenom_client,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_client' => $request->adress_client,
            'email_client' => $request->email_client,
            'tel_client' => $request->tel_client,
            'sousUtilisateur_id' => auth('apisousUtilisateur')->id(),
            'categorie_id' => $request->categorie_id,
        ]);
    
        $client->save();
    
        return response()->json(['message' => 'Client ajouté avec succès', 'client' => $client]);
    }

    public function listerClients()
{
    $clients = Client::all();

    return response()->json(['clients' => $clients]);
}

public function modifierClient(Request $request, $id)
{
    $client = Client::findOrFail($id);

    $request->validate([
        'nom_client' => 'required|string|max:255',
        'prenom_client' => 'required|string|max:255',
        'nom_entreprise' => 'required|string|max:255',
        'adress_client' => 'required|string|max:255',
        'email_client' => 'required|email|max:255',
        'tel_client' => 'required|string|max:255',
        'categorie_id' => 'required|exists:categorie_clients,id',
    ]);

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
