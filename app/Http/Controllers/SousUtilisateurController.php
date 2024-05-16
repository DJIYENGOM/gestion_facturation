<?php

namespace App\Http\Controllers;

use App\Models\Sous_Utilisateur;
use App\Http\Requests\StoreSous_UtilisateurRequest;
use App\Http\Requests\UpdateSous_UtilisateurRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
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
            'id_role' => 'required|exists:roles,id', 
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
           // 'password' => Crypt::encryptString($request->password),
            'id_role' => $request->input('id_role'), 
            'id_user' => $user->id, 
            'archiver' => 'non', 
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
        $utilisateurs = DB::table('sous__utilisateurs')
        ->select('sous__utilisateurs.id', 'sous__utilisateurs.nom', 'sous__utilisateurs.prenom', 'sous__utilisateurs.email', 'sous__utilisateurs.password', 'sous__utilisateurs.id_role', 'sous__utilisateurs.id_user', 'sous__utilisateurs.archiver', 'sous__utilisateurs.created_at', 'sous__utilisateurs.updated_at', 'roles.role as nom_role')
        ->where('sous__utilisateurs.archiver', 'non')
        ->join('roles', 'sous__utilisateurs.id_role', '=', 'roles.id')
        ->get();

        // foreach ($utilisateurs as $utilisateur) {
        //     $utilisateur->password = Crypt::decryptString($utilisateur->password);  //pour decrypter les mot de password
        //       }

    return response()->json($utilisateurs);
    }


    public function listeUtilisateurArchive()
    {
        $utilisateurs = DB::table('sous__utilisateurs')
        ->select('sous__utilisateurs.id', 'sous__utilisateurs.nom', 'sous__utilisateurs.prenom', 'sous__utilisateurs.email', 'sous__utilisateurs.password', 'sous__utilisateurs.id_role', 'sous__utilisateurs.id_user', 'sous__utilisateurs.archiver', 'sous__utilisateurs.created_at', 'sous__utilisateurs.updated_at', 'roles.role as nom_role')
        ->where('sous__utilisateurs.archiver', 'oui')
        ->join('roles', 'sous__utilisateurs.id_role', '=', 'roles.id')
        ->get();  

        // foreach ($utilisateurs as $utilisateur) {
        //     $utilisateur->password = Crypt::decryptString($utilisateur->password);  
        //       }

    return response()->json($utilisateurs);
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
        $utilisateur->password = Hash::make($request->password);
        $utilisateur->id_role = $request->input('id_role'); 
        $utilisateur->archiver = $request->input('archiver'); 
        $utilisateur->id_user = $user->id; 
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
