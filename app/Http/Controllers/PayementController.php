<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\Facture;
use App\Models\Payement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StorePayementRequest;
use App\Http\Requests\UpdatePayementRequest;

class PayementController extends Controller
{
   

  public function ajouterPayement(Request $request)
  {
    $request->validate([
        'nom_payement' => 'required|string|max:255',
    ]);

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = null;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $payement = new Payement([
        'nom_payement' => $request->input('nom_payement'),
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    $payement->save();

    return response()->json(['message' => 'Payement ajouté avec succès', 'payement' => $payement]);
 }

public function listerPayements()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $payments = Payement::where('user_id', $userId)
        ->orWhere('sousUtilisateur_id', $sousUtilisateurId ?? 0)
        ->get();

    return response()->json($payments);
}

public function modifierPayement(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $payement = Payement::where('id', $id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();

        if ($payement) {
            $request->validate([
                'nom_payement' => 'required|string|max:255',
            ]);
            $user_id = null;
            $payement->nom_payement = $request->nom_payement;
            $payement->sousUtilisateur_id = $sousUtilisateurId; // Il n'est pas nécessaire de réassigner ces IDs
            $payement->user_id =$user_id; // Utiliser l'ID existant de l'utilisateur

            $payement->save();

            return response()->json(['message' => 'Payement modifié avec succès', 'Payement' => $payement]);
        } else {
            return response()->json(['error' => 'ce sous utilisateur n\'est pas autorisé à modifier ce payement'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $payement = Payement::where('id', $id)
            ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);})
            ->first();

        if ($payement) {
            $request->validate([
                'nom_payement' => 'required|string|max:255',
            ]);
            $sousUtilisateur_id = null;   
            $payement->nom_payement = $request->nom_payement;
            $payement->user_id = $userId; // Il n'est pas nécessaire de réassigner ces IDs
            $payement->sousUtilisateur_id = $sousUtilisateur_id; // Utiliser l'ID existant du sous-utilisateur

            $payement->save();

            return response()->json(['message' => 'Payement modifié avec succès', 'Payement' => $payement]);
        } else {
            return response()->json(['error' => ' ce utilisateur n\'est pas autorisé à modifier ce payement'], 401);
        }
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
}
  

public function supprimerPayement($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $payement = Payement::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($payement){
                $payement->delete();
            return response()->json(['message' => 'Payement supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $payement = Payement::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
            if ($payement) {
                $payement->delete();
                return response()->json(['message' => 'Payement supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
            }

    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

}    

public function RapportMoyenPayement(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin'). ' 23:59:59'; //Inclure la fin de la journée
    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;

    $factures = Facture::whereBetween('date_paiement', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId);
        })
        ->with('paiement') 
        ->get();

    $facturePayements = $factures->map(function ($facture) {
        if ($facture->paiement) {
            return [
                'id_facture' => $facture->id,
                'nom_payement' => $facture->paiement->nom_payement, 
                'montant' => $facture->prix_TTC, 
            ];
        }
        return null; 
    })->filter(); // Enlever les résultats nuls

    $depenses = Depense::whereBetween('date_paiement', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId);
        })
        ->with('paiement') 
        ->get();

    $depensePayements = $depenses->map(function ($depense) {
        // Vérifier si la relation 'paiement' existe
        if ($depense->paiement) {
            return [
                'id_depense' => $depense->id,
                'nom_payement' => $depense->paiement->nom_payement,
                'montant' => $depense->montant_depense_ttc, 
            ];
        }
        return null; 
    })->filter(); 

    // Fusionner les paiements des factures et des dépenses
    $payementsUtilises = $facturePayements->merge($depensePayements);

    return response()->json(['payements_utilises' => $payementsUtilises]);
}




}
