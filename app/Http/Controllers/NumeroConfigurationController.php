<?php

namespace App\Http\Controllers;

use App\Models\NumeroConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NumeroConfigurationController extends Controller
{
    public function configurerNumeros(Request $request)
    {
        // Vérifier si l'utilisateur est connecté
        if (auth()->check()) {
            $userId = auth()->id();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Valider les données envoyées
        $validator = Validator::make($request->all(), [
            'type_document' => 'required|in:facture,livraison,produit,service,client,devis,commande,depense,fournisseur,commande_achat',
            'type_numerotation' => 'required|in:par_defaut,avec_prefixe',
            'prefixe' => 'nullable|required_if:type_numerotation,avec_prefixe|string|max:10',
            'format' => 'nullable|in:annee,annee_mois,annee_mois_jour',
        ]);

        // En cas d'échec de validation, retourner les erreurs
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mettre à jour ou créer la configuration de numérotation
        $configuration = NumeroConfiguration::updateOrCreate(
            [
                'user_id' => $userId,
                'type_document' => $request->type_document,
            ],
            [
                'type_numerotation' => $request->type_numerotation,
                'prefixe' => $request->prefixe,
                'format' => $request->format ?? null,
                'compteur' => 0, // Réinitialiser le compteur à 0 lors de la configuration
            ]
        );

        return response()->json(['message' => 'Configuration de numérotation mise à jour avec succès', 'configuration' => $configuration]);
    }

    public function InfoConfigurationFacture()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'facture')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'facture')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }
    public function InfoConfigurationDevis()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'devis')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'devis')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }

    public function InfoConfigurationBonCommande()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'commande')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'commande')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }

    public function InfoConfigurationDepense()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'depense')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'depense')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }

    public function InfoConfigurationFournisseur()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'fournisseur')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'fournisseur')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }

    public function InfoConfigurationCommandeAchat()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'commande_achat')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'commande_achat')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }

    public function InfoConfigurationLivraison()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $configuration = NumeroConfiguration::where('type_document', 'livraison')
                ->Where('user_id', $userId)
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $configuration = NumeroConfiguration::where('type_document', 'livraison')
                ->where('user_id', $userId)
                ->get();
        } else {
            return response()->json(['error' => 'Aucune configuration trouver'], 401);
        }  
    
        return response()->json(['configuration' => $configuration]);
    
    }
}
