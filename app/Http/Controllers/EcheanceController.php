<?php

namespace App\Http\Controllers;

use App\Models\Echeance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Facture;

class EcheanceController extends Controller
{
    public function creerEcheance(Request $request, $factureId)
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

        $validator = Validator::make($request->all(), [
            'date_pay_echeance' => 'required|date|after_or_equal:today',
            'montant_echeance' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $facture = Facture::find($factureId);
        if (!$facture) {
            return response()->json(['error' => 'Facture non trouvée'], 404);
        }
    
        // Créer l'échéance
        $echeance = Echeance::create([
            'facture_id' => $factureId,
            'date_pay_echeance' => $request->date_pay_echeance,
            'montant_echeance' => $request->montant_echeance,
            'commentaire' => $request->commentaire,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);
        return response()->json(['message' => 'Échéance créée avec succès', 'echeance' => $echeance], 201);
    }
    

    public function listEcheanceParFacture($factureId)
    {
      
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        Facture::findOrFail($factureId)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
            $echeances = Echeance::where('facture_id', $factureId)->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

        Facture::findOrFail($factureId)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });
            $echeances = Echeance::where('facture_id', $factureId)->get();

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

        return response()->json(['echeances' => $echeances], 200);
    }

    public function supprimerEcheance($echeanceId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
           $echeance = Echeance::where('id',$echeanceId)
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();
            if ($echeance)
                {
                    $echeance->delete();
                return response()->json(['message' => 'echeance supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet echeance'], 401);
                }
    
        }elseif (auth()->check()) {
            $userId = auth()->id();
    
            $echeance = Echeance::where('id',$echeanceId)
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
    
                if ($echeance)
                {
                    $echeance->delete();
                    return response()->json(['message' => 'echeance supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet echeance'], 401);
                }
    
        }else {
            return response()->json(['error' => 'Unauthorizedd'], 401);
        }
}

}