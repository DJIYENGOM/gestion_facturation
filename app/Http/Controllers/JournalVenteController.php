<?php

namespace App\Http\Controllers;

use App\Models\JournalVente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class JournalVenteController extends Controller
{
    public function getJournalVentesEntreDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin') . ' 23:59:59'; //Inclure la fin de la journée
        $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
        $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;
    
        $journalVentes = JournalVente::with(['facture','facture.client','articles.article','compteComptable','factureAvoir','factureAvoir.client','user','sousUtilisateur'])
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->where('id_depense',null)
            ->where(function ($query) use ($userId, $parentUserId) {
                $query->where('user_id', $userId)
                    ->orWhere('user_id', $parentUserId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                        $query->where('id_user', $parentUserId);
                    });
            })
            ->get();
    
        return response()->json($journalVentes);
    }
    
    public function getJournalAchatsEntreDates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin') . ' 23:59:59'; //Inclure la fin de la sélection
        $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
        $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;
    
        $journalAchat = JournalVente::with(['depense','depense.fournisseur','depense.categorieDepense','compteComptable','user','sousUtilisateur'])
            ->where('id_facture',null) 
            ->where('id_factureAvoir',null)
            ->whereBetween('created_at', [$dateDebut, $dateFin])
            ->where(function ($query) use ($userId, $parentUserId) {
                $query->where('user_id', $userId)
                    ->orWhere('user_id', $parentUserId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                        $query->where('id_user', $parentUserId);
                    });

            })
            ->get();

        return response()->json($journalAchat);
    
}

}