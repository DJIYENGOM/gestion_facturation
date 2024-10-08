<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TvaController extends Controller
{
    public function InfoSurTva_Recolte_Deductif_Reverse()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

            $tvas = DB::table('tvas')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->get();

        } elseif (auth()->check()) {
            $userId = auth()->id();

            $tvas = DB::table('tvas')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhere('sousUtilisateur_id', $userId);
                })
                ->get();

        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        $totalTvaRecolte = $tvas->sum('tva_recolte');
        $totalTvaDeductif = $tvas->sum('tva_deductif');
        $totalTvaReverse = $totalTvaRecolte - $totalTvaDeductif;

        $response = [
            'total_tva_recolte' => $totalTvaRecolte,
            'total_tva_deductif' => $totalTvaDeductif,
            'total_tva_reverse' => $totalTvaReverse,
        ];

        return response()->json($response, 200);
    }

    public function RapportTVA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
        $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;
    
        $FactureTVAs = ArtcleFacture::whereBetween('created_at', [$dateDebut, $dateFin])
        ->whereHas('facture', function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                  ->orWhere('user_id', $parentUserId);
        })
            ->with('facture.client') 
            ->get();
    
        $Ventes = $FactureTVAs->map(function ($FactureTVA) {
            // Vérifier si l'article a une TVA
            if ($FactureTVA->TVA_article) {
                return [
                    'num_facture' => $FactureTVA->facture->num_facture ?? 'N/A', 
                    'Prenom_Nom_client' => ($FactureTVA->facture->client->prenom_client ?? 'N/A') . ' ' . ($FactureTVA->facture->client->nom_client ?? 'N/A'), 
                    'tva' => $FactureTVA->TVA_article, 
                ];
            }
            return null; 
        })->filter(); // Enlever les résultats nuls
    
        $depenses = Depense::whereBetween('created_at', [$dateDebut, $dateFin])
            ->where(function ($query) use ($userId, $parentUserId) {
                $query->where('user_id', $userId)
                    ->orWhere('user_id', $parentUserId);
            })
            ->with('categorieDepense', 'fournisseur') 
            ->get();
    
        $depenseTVAs = $depenses->map(function ($depense) {
            if ($depense->tva_depense) {
                return [
                    'num_depense' => $depense->num_depense ?? 'N/A', 
                    'categorie' => $depense->categorieDepense->nom_categorie_depense ?? 'N/A', 
                    'tva' => $depense->tva_depense,
                ];
            }
            return null; 
        })->filter(); 
    
        $TVAsUtilises = $Ventes->merge($depenseTVAs);
    
        return response()->json(['TVAs_utilises' => $TVAsUtilises]);
    }
    
}
