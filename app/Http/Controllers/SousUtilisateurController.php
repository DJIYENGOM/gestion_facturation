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
    
    public function ajouterSousUtilisateur(Request $request)
    {
        $user = auth()->user();

        // Valider les données de la requête
        $validator=Validator::make($request->all(),[
            'nom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'email' => 'required|string|email|unique:sous__utilisateurs|max:255',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],
            'id_role' => 'required|exists:roles,id', // Vérifier que le rôle existe dans la table des rôles
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
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
        $utilisateur = Sous_Utilisateur::where('archiver', 'oui')->get();
        return response()->json($utilisateur);
    }

    public function modifierSousUtilisateur(Request $request, $id)
    {
        $user = auth()->user();
        $utilisateur = Sous_Utilisateur::findOrFail($id);
    
        // Valider les données de la requête
        $validator=Validator::make($request->all(),[
            'nom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'email' => 'required|string|email|max:255|unique:sous__utilisateurs,email,'.$id,
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],
            'id_role' => 'required|exists:roles,id', 
            'archiver' => 'required|in:oui,non' 
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }
    
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

        $utilisateur = Sous_Utilisateur::findOrFail($id);
        if($utilisateur->archiver == 'oui'){
            return response()->json(['message' => 'ce sous utilisateur est deja archivé']);
        }
    
        $utilisateur->archiver = 'oui';
    
        $utilisateur->save();
    
        return response()->json(['message' => 'vous avez archive le sous-utilisateur ' .$utilisateur->prenom ]);
    }
    
    public function Des_ArchiverSousUtilisateur(Request $request, $id)
    {

        $utilisateur = Sous_Utilisateur::findOrFail($id);
        if($utilisateur->archiver == 'non'){
            return response()->json(['message' => 'ce sous utilisateur est deja desarchivé']);
        }
    
        $utilisateur->archiver = 'non';
    
        $utilisateur->save();
    
        return response()->json(['message' => 'vous avez desarchive le sous-utilisateur ' .$utilisateur->prenom ]);
    }
}
