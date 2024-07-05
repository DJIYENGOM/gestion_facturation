<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use Illuminate\Http\Request;
use App\Models\CompteComptable;
use Illuminate\Support\Facades\Validator;
use App\Services\NumeroGeneratorService;

class FournisseurController extends Controller
{

    public function ajouterFournisseur(Request $request)
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
            'type_fournisseur' => 'required|in:particulier,entreprise',
            'num_fournisseur' => 'nullable|string|unique:fournisseurs,num_fournisseur',
            'doc_fournisseur' => 'nullable|file|mimes:pdf,doc,docx,excel,xls,xlsx|max:10240',

        ];
    
        $particulierRules = [
            'nom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        ];
    
        $entrepriseRules = [
            'num_id_fiscal' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:50|min:2',

        ];
    
        $additionalRules = [
            'email_fournisseur' => 'required|email|max:255',
            'tel_fournisseur' => 'required|string|max:20|min:9',

        ];
    
        $rules = array_merge($commonRules, $additionalRules);
    
        if ($request->type_fournisseur == 'particulier') {
            $rules = array_merge($rules, $particulierRules);
        } elseif ($request->type_fournisseur == 'entreprise') {
            $rules = array_merge($rules, $entrepriseRules);
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        
        if (!$request->has('id_comptable')) {
                $compte = CompteComptable::where('nom_compte_comptable', 'fournisseurs divers')
                                         ->first();
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        $fournisseur = new fournisseur([
            'num_fournisseur' => $request->num_fournisseur,
            'nom_fournisseur' => $request->nom_fournisseur,
            'prenom_fournisseur' => $request->prenom_fournisseur,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_fournisseur' => $request->adress_fournisseur,
            'email_fournisseur' => $request->email_fournisseur,
            'tel_fournisseur' => $request->tel_fournisseur,
            'type_fournisseur' => $request->type_fournisseur,
            'num_id_fiscal' => $request->num_id_fiscal,
            'code_postal_fournisseur' => $request->code_postal_fournisseur,
            'ville_fournisseur' => $request->ville_fournisseur,
            'pays_fournisseur' => $request->pays_fournisseur,
            'noteInterne_fournisseur' => $request->noteInterne_fournisseur,
            'doc_fournisseur' => $request->doc_fournisseur,
           
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
            'categorie_id' => $request->categorie_id,
            'id_comptable' => $id_comptable,
        ]);
    
        $fournisseur->save();
    
        return response()->json(['message' => 'fournisseur ajouté avec succès', 'fournisseur' => $fournisseur]);
    }

    public function listerTousFournisseurs()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $fournisseurs = Fournisseur::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $fournisseurs = Fournisseur::where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        return response()->json($fournisseurs);
    }

    public function modifierFournisseur(Request $request, $id)
{
    $fournisseur = Fournisseur::findOrFail($id);

    // Déterminer l'utilisateur authentifié
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        if ($fournisseur->sousUtilisateur_id !== $sousUtilisateurId) {
            return response()->json(['error' => 'Cette sous-utilisateur ne peut pas modifier ce fournisseur car il ne l\'a pas créé'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();
        if ($fournisseur->user_id !== $userId) {
            return response()->json(['error' => 'Cet utilisateur ne peut pas modifier ce fournisseur car il ne l\'a pas créé'], 401);
        }
    } else {
        return response()->json(['error' => 'Non autorisé'], 401);
    }

    $commonRules = [
        'type_fournisseur' => 'required|in:particulier,entreprise',
        'num_fournisseur' => 'nullable|string|unique:fournisseurs,num_fournisseur',
        'doc_fournisseur' => 'nullable|file|mimes:pdf,doc,docx,excel,xls,xlsx|max:10240',

    ];

    $particulierRules = [
        'nom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'prenom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    ];

    $entrepriseRules = [
        'num_id_fiscal' => 'required|string|max:255',
        'nom_entreprise' => 'required|string|max:50|min:2',

    ];

    $additionalRules = [
        'email_fournisseur' => 'required|email|max:255',
        'tel_fournisseur' => 'required|string|max:20|min:9',

    ];

    $rules = array_merge($commonRules, $additionalRules);

    if ($request->type_fournisseur == 'particulier') {
        $rules = array_merge($rules, $particulierRules);
    } elseif ($request->type_fournisseur == 'entreprise') {
        $rules = array_merge($rules, $entrepriseRules);
    }
    // Valider les données reçues
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Mettre à jour les données du fournisseur
    $fournisseur->update($request->all());

    return response()->json(['message' => 'fournisseur modifié avec succès', 'fournisseur' => $fournisseur]);
}

public function supprimerFournisseur($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $fournisseur = Fournisseur::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($fournisseur){
                $fournisseur->delete();
            return response()->json(['message' => 'fournisseur supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce fournisseur'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $fournisseur = Fournisseur::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            if($fournisseur){
                $fournisseur->delete();
                return response()->json(['message' => 'fournisseur supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce fournisseur'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}
}
