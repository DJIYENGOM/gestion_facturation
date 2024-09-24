<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\EmailModele;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Sous_Utilisateur;
use App\Models\NumeroConfiguration;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'login_sousUtilisateur']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);
        
        if (!$token) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();
        return response()->json([
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
    public function register(Request $request)
    {
        $validator=Validator::make($request->all(),[
            'name' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $typesDocument = [
            'facture',
            'livraison',
            'produit',
            'service',
            'client',
            'devis',
            'commande',
            'depense',
            'fournisseur',
            'commande_achat'
        ];

        try {
            foreach ($typesDocument as $type) {
                NumeroConfiguration::create([
                    'user_id' => $user->id,
                    'type_document' => $type,
                    'type_numerotation' => 'par_defaut',
                    'prefixe' => '',
                    'compteur' => 0
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error inserting NumeroConfiguration: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erreur lors de la création des configurations',
                'error' => $e->getMessage()
            ], 500);
        }

        Notification::create([
            'produit_rupture' => 1,
            'depense_impayere' => 1,
            'payement_attente' => 1,
            'devis_expirer' => 1,
            'relance_automatique' => 1,

            'quantite_produit'=> 5,
            'nombre_jourNotif_brouillon' => 7,
            'nombre_jourNotif_depense' => 7,
            'nombre_jourNotif_echeance' => 7,
            'nombre_jourNotif_devis' => 7,
            'recevoir_notifications' => 1,
            'user_id' => $user->id
        ]);
        $this->createDefaultEmailModels($user);


        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function createDefaultEmailModels(User $user)
    {
        $emailTemplates = [
            'facture' => [
                'object' => 'Facture {VENTE_NUMERO} du {VENTE_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\n Veuillez trouver ci-joint la Facture N° {VENTE_NUMERO} du {VENTE_DATE} pour un montant de {VENTE_PRIX_TOTAL}.\n\nNous vous remercions de votre confiance.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'devi' => [
                'object' => 'Devis {DEVIS_NUMERO} réalisé le {DEVIS_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint le Devis N° {DEVIS_NUMERO} réalisé le {DEVIS_DATE}.\n\nMontant total du devis : {DEVIS_PRIX_TOTAL}.\n\nSi vous avez besoin d'informations supplémentaires, n'hésitez pas à nous contacter.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'resumer_vente' => [
                'object' => 'Résumé de vente du {VENTE_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint le résumé de la Vente N° {VENTE_NUMERO} du {VENTE_DATE} pour un montant de {VENTE_PRIX_TOTAL}.\n\n Nous vous remercions de votre confiance.\n\nÀ bientôt !\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'recu_paiement' => [
                'object' => 'Reçu de paiement {PAIEMENT_NUMERO} du {PAIEMENT_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nVeuillez trouver ci-joint votre reçu de paiement N° {PAIEMENT_NUMERO} du {PAIEMENT_DATE}.\n\nMontant payé : {PAIEMENT_MONTANT}.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'relanceAvant_echeance' => [
                'object' => 'Relance avant échéance pour la Facture N° {VENTE_NUMERO}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nNous vous rappelons que votre facture N° {VENTE_NUMERO} arrivera à échéance le {ECHEANCE_DATE}.\n\nMontant dû : {ECHEANCE_MONTANT}.\n\nMerci de procéder au règlement avant cette date.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'relanceApres_echeance' => [
                'object' => 'Relance après échéance pour la Facture N° {VENTE_NUMERO}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nNous vous informons que la facture N° {VENTE_NUMERO} est échue depuis le {ECHEANCE_DATE}.\n\nMontant dû : {ECHEANCE_MONTANT}.\n\nMerci de régulariser cette situation au plus vite.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'commande_vente' => [
                'object' => 'Confirmation de commande N° {COMMANDE_NUMERO} du {COMMANDE_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nVotre commande N° {COMMANDE_NUMERO} a bien été enregistrée le {COMMANDE_DATE}.\n\nMontant total : {COMMANDE_MONTANT}.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'livraison' => [
                'object' => 'Livraison N° {LIVRAISON_NUMERO} effectuée le {LIVRAISON_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\n Veuillez trouver ci-joint le Bon de Livraison N° {LIVRAISON_NUMERO}.\n\nLivraison prévue le : {LIVRAISON_DATE}.\n\nMerci de nous avoir fait confiance.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
            'fournisseur' => [
                'object' => 'Bon de commande N° {BON_NUMERO} du {BON_DATE}',
                'contenu' => "Bonjour {DESTINATAIRE},\n\nVoici le bon de commande N° {BON_NUMERO} daté du {BON_DATE}.\n\nCordialement,\n\n{ENTREPRISE}"
            ],
        ];
    
        foreach ($emailTemplates as $type => $template) {
            EmailModele::create([
                'type_modele' => $type,
                'object' => $template['object'],
                'contenu' => $template['contenu'],
                'user_id' => $user->id,
                'sousUtilisateur_id' => null,
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }
    public function login_sousUtilisateur(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        $sousUtilisateur = Sous_Utilisateur::where('email', $request->email)->first();
    
        if (!$sousUtilisateur) {
            return response()->json([
                'message' => 'non autorisé - utilisateur non trouver',
            ], 401);
        }
    
        if ($sousUtilisateur->archiver == 'oui') {
            return response()->json([
                'message' => 'non autorisé - Ce compte a été archivé',
            ], 401);
        }
    
        $credentials = $request->only('email', 'password');
        $token = Auth::guard('apisousUtilisateur')->attempt($credentials);
    
        if (!$token) {
            return response()->json([
                'message' => 'Non autorisé - Identifiants invalides ',               
            ], 401);
        }
    
        return response()->json([
            'user' => $sousUtilisateur,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ]
        ]);
    }
    


    public function logout_sousUtilisateur()
{
    auth('apisousUtilisateur')->logout(); // Utilisez le garde 'apisousUtilisateur' pour déconnecter les sous-utilisateurs
    return response()->json([
        'message' => 'Successfully logged out',
    ]);
}

public function refresh_sousUtilisateur()
    {
        return response()->json([
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    public function listerUser()
    {
        $users = User::where('id', '=', Auth::user()->id)->get();
    
        return response()->json(['user' => $users]);
    }



public function modifierMotDePasse(Request $request)
{
    $validator=Validator::make($request->all(),[
        'name' => ['nullable', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'email' => 'nullable|string|email|max:255|unique:users',
        'mot_de_passe_actuel' => 'required',
        'nouveau_mot_de_passe' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/'],

    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
        ],422);
    }

    // Récupérer l'utilisateur connecté
    $utilisateur = Auth::user();

    // Vérification du mot de passe actuel
    if (!Hash::check($request->mot_de_passe_actuel, $utilisateur->password)) {
        return response()->json(['mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect.']);
    }

    $utilisateur->password = Hash::make($request->nouveau_mot_de_passe);
    $utilisateur->name = $request->input('name');
    $utilisateur->email = $request->input('email');
    $utilisateur->save();

    return response()->json(['message' => 'utilisateur mis à jour avec succes.']);
}


}
