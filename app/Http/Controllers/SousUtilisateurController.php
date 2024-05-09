<?php

namespace App\Http\Controllers;

use App\Models\Sous_Utilisateur;
use App\Http\Requests\StoreSous_UtilisateurRequest;
use App\Http\Requests\UpdateSous_UtilisateurRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;


class SousUtilisateurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function ajouterSousUtilisateur(Request $request)
    {
        $user = auth()->user();

        // Valider les données de la requête
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|unique:sous__utilisateurs|max:255',
            'password' => 'required|string|min:6',
            'id_role' => 'required|exists:roles,id', // Vérifier que le rôle existe dans la table des rôles
        ]);
    
        // Créer un nouvel utilisateur
        $utilisateur = new Sous_Utilisateur([
            'nom' => $request->input('nom'),
            'prenom' => $request->input('prenom'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->password),
            'id_role' => $request->input('id_role'), // Utiliser le rôle choisi par l'utilisateur
            'id_user' => $user->id, // ID de l'utilisateur connecté  ou utiliser  ''id_user' => Auth::id(),
            'archiver' => 'non', // Par défaut, l'utilisateur n'est pas archivé
        ]);

        //dd($utilisateur);

        $utilisateur->save();
    
        return response()->json(['message' => 'Utilisateur ajouté avec succès']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function listeUtilisateurNonArchive()
    {
        $utilisateur = Sous_Utilisateur::where('archiver', 'non')->get();
        return response()->json($utilisateur);
    }


    public function listeUtilisateurArchive()
    {
        $utilisateur = Sous_Utilisateur::where('archiver', 'non')->get();
        return response()->json($utilisateur);
    }

    public function modifierSousUtilisateur(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();
    
        // Récupérer le sous-utilisateur à modifier
        $utilisateur = Sous_Utilisateur::findOrFail($id);
    
        // Vérifier que l'utilisateur connecté est le propriétaire du sous-utilisateur
        if ($user->id !== $utilisateur->id_user) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à modifier cet utilisateur'], 403);
        }
    
        // Valider les données de la requête
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:sous__utilisateurs,email,'.$id,
            'password' => 'nullable|string|min:6',
            'id_role' => 'required|exists:roles,id', // Vérifier que le rôle existe dans la table des rôles
            'archiver' => 'required|in:oui,non' // Valider que la valeur est soit "oui" soit "non"
        ]);
    
        // Mettre à jour les attributs du sous-utilisateur
        $utilisateur->nom = $request->input('nom');
        $utilisateur->prenom = $request->input('prenom');
        $utilisateur->email = $request->input('email');
        if ($request->has('password')) {
            $utilisateur->password = Hash::make($request->input('password'));
        }
        $utilisateur->id_role = $request->input('id_role'); // Utiliser le rôle choisi par l'utilisateur
        $utilisateur->archiver = $request->input('archiver'); // Mettre à jour le champ archiver
        $utilisateur->id_user = $user->id; 
        // Enregistrer les modifications
        $utilisateur->save();
    
        return response()->json(['message' => 'Utilisateur modifié avec succès']);
    }
    
    public function ArchiverSousUtilisateur(Request $request, $id)
    {
        // Récupérer l'utilisateur connecté
        $user = auth()->user();
    
        // Récupérer le sous-utilisateur à modifier
        $utilisateur = Sous_Utilisateur::findOrFail($id);
    
        // Vérifier que l'utilisateur connecté est le propriétaire du sous-utilisateur
        if ($user->id !== $utilisateur->id_user) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à modifier le statut cet utilisateur'], 403);
        }
    
        // Valider la donnée 'archiver' de la requête
        $request->validate([
            'archiver' => 'required|in:oui,non' // Valider que la valeur est soit "oui" soit "non"
        ]);
    
        // Mettre à jour le champ 'archiver' du sous-utilisateur
        $utilisateur->archiver = $request->input('archiver');
    
        // Enregistrer les modifications
        $utilisateur->save();
    
        return response()->json(['message' => 'le statut cet sous-utilisateur a été modifié avec succès']);
    }
    
}
