<?php

namespace App\Http\Controllers;

use App\Models\ArtcleFacture;
use App\Models\ArticleDevi;
use App\Models\Devi;
use App\Models\Echeance;
use App\Models\Facture;
use App\Models\FactureAccompt;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class DeviController extends Controller
{
    public function creerDevi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_devi' => 'nullable|string',
            'reduction_devi' => 'nullable|numeric',
            'date_devi'=>'required|date',
            'date_limite'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'statut_devi'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',
            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'required|date',
            'echeances.*.montant_echeance' => 'required|numeric|min:0',
            'facture_accompts' => 'nullable|array',
            'facture_accompts.*.titreAccomp' => 'required|string',
            'facture_accompts.*.dateAccompt' => 'required|date',
            'facture_accompts.*.dateEcheance' => 'required|date',
            'facture_accompts.*.montant' => 'required|numeric|min:0',
            'facture_accompts.*.commentaire' => 'nullable|string',
            'articles' => 'required|array',
            'articles.*.id_article' => 'required|exists:articles,id',
            'articles.*.quantite_article' => 'required|integer',
            'articles.*.prix_unitaire_article' => 'required|numeric',
            'articles.*.TVA_article' => 'nullable|numeric',
            'articles.*.reduction_article' => 'nullable|numeric',
            'articles.*.prix_total_article'=>'nullable|numeric',
            'articles.*.prix_total_tva_article'=>'nullable|numeric'
        ]);
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Création de la facture
        $devi = Devi::create([
            'client_id' => $request->client_id,
            'date_devi' => $request->date_devi,
            'date_limite' => $request->date_limite,
            'reduction_devi' => $request->input('reduction_facture', 0),
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_devi' => $request->input('note_fact'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_devi' => $request->statut_devi ?? 'en_attente',
        ]);
    
        $devi->num_devi = Devi::generateNumdevi($devi->id);
        $devi->save();
    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleDevi::create([
                'id_devi' => $devi->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
        if ($request->has('echeances')) {
            foreach ($request->echeances as $echeanceData) {
                Echeance::create([
                    'devi_id' => $devi->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }
    
        // Gestion des factures d'acompte si type_paiement est 'facture_Accompt'
        if ($request->has('facture_accompts')) {
            foreach ($request->facture_accompts as $accomptData) {
                FactureAccompt::create([
                    'devi_id' => $devi->id,
                    'titreAccomp' => $accomptData['titreAccomp'],
                    'dateAccompt' => $accomptData['dateAccompt'],
                    'dateEcheance' => $accomptData['dateEcheance'],
                    'montant' => $accomptData['montant'],
                    'commentaire' => $accomptData['commentaire'] ?? '',
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
                Echeance::create([
                    'devi_id' => $devi->id,
                    'date_pay_echeance' => $accomptData['dateEcheance'],
                    'montant_echeance' => $accomptData['montant'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }
        return response()->json(['message' => 'Devi créée avec succès', 'Devi' => $devi], 201);

    }
public function TransformeDeviEnFacture(Request $request, $deviId)
{
    $devi = Devi::find($deviId);
    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

    $validator = Validator::make($request->all(), [
        'client_id' => 'required|exists:clients,id',
        'note_fact' => 'nullable|string',
        'reduction_facture' => 'nullable|numeric',
        'active_Stock'=> 'nullable|in:oui,non',
        'prix_HT'=> 'required|numeric',
        'prix_TTC'=>'required|numeric',
        'type_paiement' => 'required|in:immediat,echeance,facture_Accompt',
        'id_paiement' => 'nullable|required_if:type_paiement,immediat|exists:payements,id',
        'echeances' => 'nullable|required_if:type_paiement,echeance|array',
        'echeances.*.date_pay_echeance' => 'required|date',
        'echeances.*.montant_echeance' => 'required|numeric|min:0',
        'facture_accompts' => 'nullable|required_if:type_paiement,facture_Accompt|array',
        'facture_accompts.*.titreAccomp' => 'required|string',
        'facture_accompts.*.dateAccompt' => 'required|date',
        'facture_accompts.*.dateEcheance' => 'required|date',
        'facture_accompts.*.montant' => 'required|numeric|min:0',
        'facture_accompts.*.commentaire' => 'nullable|string',
        'articles' => 'required|array',
        'articles.*.id_article' => 'required|exists:articles,id',
        'articles.*.quantite_article' => 'required|integer',
        'articles.*.prix_unitaire_article' => 'required|numeric',
        'articles.*.TVA_article' => 'nullable|numeric',
        'articles.*.reduction_article' => 'nullable|numeric',
        'articles.*.prix_total_article'=>'nullable|numeric',
        'articles.*.prix_total_tva_article'=>'nullable|numeric'
    ]);
if ($validator->fails()) {
    return response()->json(['errors' => $validator->errors()], 422);
}

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }


    $statutPaiement = $request->type_paiement === 'immediat' ? 'payer' : 'en_attente';

    $datePaiement = $request->type_paiement === 'immediat' ? now() : null;
    // Création de la facture
    $facture = Facture::create([
        'client_id' => $request->client_id,
        'date_creation' => now(),
        'date_paiement' => $datePaiement,
        'reduction_facture' => $request->input('reduction_facture', 0),
        'active_Stock' => $request->active_Stock ?? 'oui',
        'prix_HT'=>$request->prix_HT,
        'prix_TTC' =>$request->prix_TTC,
        'note_fact' => $request->input('note_fact'),
        'archiver' => 'non',
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'type_paiement' => $request->input('type_paiement'),
        'statut_paiement' => $statutPaiement,
        'id_paiement' => $request->type_paiement == 'immediat' ? $request->id_paiement : null,

        'devi_id'=>$devi->id,
    ]);

    $facture->num_fact = Facture::generateNumFacture($facture->id);
    $facture->save();

    // Ajouter les articles à la facture
    foreach ($request->articles as $articleData) {
        $quantite = $articleData['quantite_article'];
        $prixUnitaire = $articleData['prix_unitaire_article'];
        $TVA = $articleData['TVA_article'] ?? 0;
        $reduction = $articleData['reduction_article'] ?? 0;
        // $prixTotalArticleTva = $quantite * $prixUnitaire * (1 + $TVA / 100) * (1 - $reduction / 100);
        // $prixTotalArticle = $quantite * $prixUnitaire * (1 - $reduction / 100);

        $prixTotalArticle=$articleData['prix_total_article'];
        $prixTotalArticleTva=$articleData['prix_total_tva_article'];

        ArtcleFacture::create([
            'id_facture' => $facture->id,
            'id_article' => $articleData['id_article'],
            'quantite_article' => $quantite,
            'prix_unitaire_article' => $prixUnitaire,
            'TVA_article' => $TVA,
            'reduction_article' => $reduction,
            'prix_total_article' => $prixTotalArticle,
            'prix_total_tva_article' => $prixTotalArticleTva,
        ]);
    }

    // Gestion des échéances si type_paiement est 'echeance'
    if ($request->type_paiement == 'echeance') {
        foreach ($request->echeances as $echeanceData) {
            Echeance::create([
                'facture_id' => $facture->id,
                'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                'montant_echeance' => $echeanceData['montant_echeance'],
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
    }

    // Gestion des factures d'acompte si type_paiement est 'facture_Accompt'
    if ($request->type_paiement == 'facture_Accompt') {
        foreach ($request->facture_accompts as $accomptData) {
            FactureAccompt::create([
                'facture_id' => $facture->id,
                'titreAccomp' => $accomptData['titreAccomp'],
                'dateAccompt' => $accomptData['dateAccompt'],
                'dateEcheance' => $accomptData['dateEcheance'],
                'montant' => $accomptData['montant'],
                'commentaire' => $accomptData['commentaire'] ?? '',
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
            Echeance::create([
                'facture_id' => $facture->id,
                'date_pay_echeance' => $accomptData['dateEcheance'],
                'montant_echeance' => $accomptData['montant'],
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
    }

    //gestion paiements reçus        
    if ($request->type_paiement == 'immediat') {
            PaiementRecu::create([
                'facture_id' => $facture->id,
                'id_paiement' => $request->id_paiement,
                'date_reçu' => now(),
                'montant' => $facture->prix_TTC,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        
    }

    return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture], 201);
}


}
  

