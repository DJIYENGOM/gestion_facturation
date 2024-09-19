<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Sous_Utilisateur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StoreSous_UtilisateurRequest;
use App\Http\Requests\UpdateSous_UtilisateurRequest;


class SousUtilisateurController extends Controller
{
    
    public function ajouterSousUtilisateur(Request $request)
    {
        if (auth()->check()) {
        $user = auth()->user();

        // Valider les données de la requête
        $validator=Validator::make($request->all(),[
            'nom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'email' => 'required|string|email|unique:sous__utilisateurs|max:255',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],
            'role'=> 'required|in:administrateur,utilisateur_simple',
            'visibilite_globale'=> 'nullable|in:1,0',
            'fonction_admin'=> 'nullable|in:1,0',
            'acces_rapport'=> 'nullable|in:1,0',
            'gestion_stock'=> 'nullable|in:1,0',
            'commande_achat'=> 'nullable|in:1,0',
            'export_excel'=> 'nullable|in:1,0',
            'supprimer_donnees'=> 'nullable|in:1,0',
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
            'id_role' => $request->input('id_role'), 
            'id_user' => $user->id, 
            'archiver' => 'non', 
            'role' => $request->input('role'),

            'visibilite_globale' => $request->input('visibilite_globale'),
            'fonction_admin' => $request->input('fonction_admin'),
            'acces_rapport' => $request->input('acces_rapport'),
            'gestion_stock' => $request->input('gestion_stock'),
            'commande_achat' => $request->input('commande_achat'),
            'export_excel' => $request->input('export_excel'),
            'supprimer_donnees' => $request->input('supprimer_donnees'),
        ]);

        //dd($utilisateur);

        $utilisateur->save();
    
        return response()->json(['message' => 'Utilisateur ajouté avec succès' , 'utilisateur' => $utilisateur]);
         }
          return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }


    public function modifierSousUtilisateur(Request $request, $id)
    {
        if (auth()->check()) {
            $user = auth()->user();
    
            // Valider les données de la requête
            $validator = Validator::make($request->all(), [
                'nom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
                'prenom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('sous__utilisateurs')->ignore($id)],
                'mot_de_passe_actuel' => 'nullable|string', 
                'password' => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'], 
                'role'=> 'required|in:administrateur,utilisateur_simple',
                'visibilite_globale'=> 'nullable|in:1,0',
                'fonction_admin'=> 'nullable|in:1,0',
                'acces_rapport'=> 'nullable|in:1,0',
                'gestion_stock'=> 'nullable|in:1,0',
                'commande_achat'=> 'nullable|in:1,0',
                'export_excel'=> 'nullable|in:1,0',
                'supprimer_donnees'=> 'nullable|in:1,0',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors(),
                ], 422);
            }
    
            $utilisateur = Sous_Utilisateur::find($id);
    
            if (!$utilisateur) {
                return response()->json(['error' => 'Sous-utilisateur introuvable'], 404);
            }
    
            if ($request->filled('mot_de_passe_actuel') && $request->filled('password')) {
                if (!Hash::check($request->input('mot_de_passe_actuel'), $utilisateur->password)) {
                    return response()->json(['error' => 'Le mot de passe actuel est incorrect.'], 422);
                }
    
                // Si le mot de passe actuel est correct, on modifie le mot de passe
                $utilisateur->password = Hash::make($request->input('password'));
            }
    
            $utilisateur->nom = $request->input('nom');
            $utilisateur->prenom = $request->input('prenom');
            $utilisateur->email = $request->input('email');
            $utilisateur->role = $request->input('role');
            $utilisateur->visibilite_globale = $request->input('visibilite_globale');
            $utilisateur->fonction_admin = $request->input('fonction_admin');
            $utilisateur->acces_rapport = $request->input('acces_rapport');
            $utilisateur->gestion_stock = $request->input('gestion_stock');
            $utilisateur->commande_achat = $request->input('commande_achat');
            $utilisateur->export_excel = $request->input('export_excel');
            $utilisateur->supprimer_donnees = $request->input('supprimer_donnees');
            $utilisateur->id_user = $user->id;
    
            $utilisateur->save();
    
            return response()->json(['message' => 'Sous-utilisateur mis à jour avec succès', 'utilisateur' => $utilisateur]);
        }
    
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }
    
    
    
    
    public function listeUtilisateurNonArchive()
    {
        if (auth()->check()) {
            $user = auth()->user();

        $utilisateurs = DB::table('sous__utilisateurs')
        ->select('sous__utilisateurs.id', 'sous__utilisateurs.nom', 'sous__utilisateurs.prenom', 'sous__utilisateurs.email', 'sous__utilisateurs.password', 'sous__utilisateurs.role', 'sous__utilisateurs.id_user', 'sous__utilisateurs.archiver',
         'sous__utilisateurs.visibilite_globale', 'sous__utilisateurs.fonction_admin', 'sous__utilisateurs.acces_rapport', 'sous__utilisateurs.gestion_stock', 'sous__utilisateurs.commande_achat', 'sous__utilisateurs.export_excel', 'sous__utilisateurs.supprimer_donnees',
         'sous__utilisateurs.created_at', 'sous__utilisateurs.updated_at')
        ->where('sous__utilisateurs.archiver', 'non' )
        ->where('sous__utilisateurs.id_user', $user->id)
        ->get();

         return response()->json($utilisateurs);
        }
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);

    }


    public function listeUtilisateurArchive()
    {
        if (auth()->check()) {
            $user = auth()->user();

        $utilisateurs = DB::table('sous__utilisateurs')
        ->select('sous__utilisateurs.id', 'sous__utilisateurs.nom', 'sous__utilisateurs.prenom', 'sous__utilisateurs.email', 'sous__utilisateurs.password', 'sous__utilisateurs.role', 'sous__utilisateurs.id_user', 'sous__utilisateurs.archiver', 'sous__utilisateurs.created_at', 'sous__utilisateurs.updated_at',)
        ->where('sous__utilisateurs.archiver', 'oui')
        ->where('sous__utilisateurs.id_user', $user->id)
        ->get();  

          return response()->json($utilisateurs);
       }
       return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);

    }
    // public function modifierSousUtilisateur(Request $request, $id)
    // {
    //     $user = auth()->user();
    //     $utilisateur = Sous_Utilisateur::findOrFail($id);
    
    //     // Valider les données de la requête
    //     $validator=Validator::make($request->all(),[
    //         'nom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    //         'prenom' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    //         'email' => 'required|string|email|max:255|unique:sous__utilisateurs,email,'.$id,
    //         'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],
    //         'role'=> 'required|in:administrateur,utilisateur_simple',
    //         'archiver' => 'required|in:oui,non' ,
    //         'visibilite_globale'=> 'nullable|in:1,0',
    //         'fonction_admin'=> 'nullable|in:1,0',
    //         'acces_rapport'=> 'nullable|in:1,0',
    //         'gestion_stock'=> 'nullable|in:1,0',
    //         'commande_achat'=> 'nullable|in:1,0',
    //         'export_excel'=> 'nullable|in:1,0',
    //         'supprimer_donnees'=> 'nullable|in:1,0',


    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],422);
    //     }
    
    //     // Mettre à jour les attributs du sous-utilisateur
    //     $utilisateur->nom = $request->input('nom');
    //     $utilisateur->prenom = $request->input('prenom');
    //     $utilisateur->email = $request->input('email');
    //     $utilisateur->password = Hash::make($request->password);
    //     $utilisateur->role = $request->input('role'); 
    //     $utilisateur->archiver = $request->input('archiver'); 
    //     $utilisateur->id_user = $user->id; 
    //     $utilisateur->visibilite_globale = $request->input('visibilite_globale');
    //     $utilisateur->fonction_admin = $request->input('fonction_admin');
    //     $utilisateur->acces_rapport = $request->input('acces_rapport');
    //     $utilisateur->gestion_stock = $request->input('gestion_stock');
    //     $utilisateur->commande_achat = $request->input('commande_achat');
    //     $utilisateur->export_excel = $request->input('export_excel');
    //     $utilisateur->supprimer_donnees = $request->input('supprimer_donnees');
    //     $utilisateur->save();
    
    //     return response()->json(['message' => 'Utilisateur modifié avec succès', 'utilisateur' => $utilisateur]);
    // }
    
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
    
    public function DesArchiverSousUtilisateur(Request $request, $id)
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
