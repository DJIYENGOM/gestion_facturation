<?php

namespace App\Http\Controllers;

use App\Models\ArtcleFacture;
use App\Models\ArticleFactureRecurrente;
use App\Models\Echeance;
use App\Models\Facture;
use App\Models\FactureRecurrente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NumeroGeneratorService;


class FactureRecurrenteController extends Controller
{
    public function creerFactureRecurrente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'num_fact'=>'nullable|string',
            'periode'=>'required|in:jour,semaine,mois',
            'nombre_periode'=>'required|integer',
            'date_debut'=>'required|date',
            'client_id' => 'required|exists:clients,id',
            'note_interne' => 'nullable|string',
            'commentaire'=>'nullable|string',
            'etat_brouillon'=> 'required|boolean',
            'envoyer_mail'=> 'required|boolean',
            'active_Stock'=> 'nullable|in:oui,non',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
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
    
        $factureRecurrente = FactureRecurrente::create([
            'periode' => $request->input('periode'),
            'nombre_periode' => $request->input('nombre_periode'),
            'date_debut' => $request->input('date_debut'),
            'client_id' => $request->input('client_id'),
            'note_interne' => $request->input('note_interne'),
            'commentaire' => $request->input('commentaire'),
            'etat_brouillon' => $request->input('etat_brouillon'),
            'envoyer_mail' => $request->input('envoyer_mail'),
            'active_Stock' => $request->input('active_Stock') ?? 'non',
            'creation_automatique'=> 1,
            'prix_HT' => $request->input('prix_HT'),
            'prix_TTC' => $request->input('prix_TTC'),
        ]);
    
        // Générer la première facture si ce n'est pas un brouillon
        if ($request->etat_brouillon == 0){
            $this->genererFacture($factureRecurrente, $userId, $sousUtilisateurId, $request->articles, $request->input('date_debut'));
        }else{
            $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $facture = Facture::create([
            'num_fact' => $numFacture,
            'client_id' => $factureRecurrente->client_id,
            'date_creation' => now(),
            'date_paiement' => $factureRecurrente->date_debut,
            'active_Stock' => $factureRecurrente->active_Stock ?? 'oui',
            'prix_HT' => $factureRecurrente->prix_HT,
            'prix_TTC' => $factureRecurrente->prix_TTC,
            'note_fact' => $factureRecurrente->note_interne,
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'type_paiement' => 'echeance',
            'statut_paiement' => 'en_attente',
            'id_paiement' => null,
            'id_recurrent' => $factureRecurrente->id,
        ]);

        }
    
        return response()->json(['message' => 'Facture récurrente créée avec succès', 'factureRecurrente' => $factureRecurrente], 201);
    }
    
    private function genererFacture($factureRecurrente, $userId, $sousUtilisateurId, $articles, $date)
    {
        $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $facture = Facture::create([
            'num_fact' => $numFacture,
            'client_id' => $factureRecurrente->client_id,
            'date_creation' => now(),
            'date_paiement' => $date,
            'active_Stock' => $factureRecurrente->active_Stock ?? 'oui',
            'prix_HT' => $factureRecurrente->prix_HT,
            'prix_TTC' => $factureRecurrente->prix_TTC,
            'note_fact' => $factureRecurrente->note_interne,
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'type_paiement' => 'echeance',
            'statut_paiement' => 'en_attente',
            'id_paiement' => null,
            'id_recurrent' => $factureRecurrente->id,

        ]);
    
        foreach ($articles as $articleData) {
            ArtcleFacture::create([
                'id_facture' => $facture->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $articleData['quantite_article'],
                'prix_unitaire_article' => $articleData['prix_unitaire_article'],
                'TVA_article' => $articleData['TVA_article'] ?? 0,
                'reduction_article' => $articleData['reduction_article'] ?? 0,
                'prix_total_article' => $articleData['prix_total_article'] ?? 0,
                'prix_total_tva_article' => $articleData['prix_total_tva_article'] ?? 0,
            ]);
        }
    
        Echeance::create([
            'facture_id' => $facture->id,
            'date_pay_echeance' => $facture->date_paiement,
            'montant_echeance' => $facture->prix_TTC,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    }
    
}
