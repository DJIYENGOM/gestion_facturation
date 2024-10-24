<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureRecurrente;
use Illuminate\Support\Facades\Artisan;
use App\Models\ArticleFactureRecurrente;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;


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
            'type_reccurente'=>'required|in:creer_brouillon,envoyer_email',
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
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $factureRecurrente = FactureRecurrente::create([
            'periode' => $request->input('periode'),
            'nombre_periode' => $request->input('nombre_periode'),
            'date_debut' => $request->input('date_debut'),
            'client_id' => $request->input('client_id'),
            'note_interne' => $request->input('note_interne'),
            'commentaire' => $request->input('commentaire'),
            'type_reccurente' => $request->input('type_reccurente'),
            'active_Stock' => $request->input('active_Stock') ?? 'non',
            'creation_automatique'=> 1,
            'prix_HT' => $request->input('prix_HT'),
            'prix_TTC' => $request->input('prix_TTC'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    

        $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $facture = Facture::create([
            'num_facture' => $numFacture,
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
        Artisan::call(command: 'optimize:clear');


        $echance = Echeance::create([
            'facture_id' => $facture->id,
            'date_echeance' => $factureRecurrente->date_debut,
            'statut' => 'en_attente',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des Factures ont été créées',
            'id_facture' => $facture->id
        ]);
    
        return response()->json(['message' => 'Facture récurrente créée avec succès', 'factureRecurrente' => $factureRecurrente, 'facture' => $facture], 201);
    }
 

    public function listerToutesFacturesRecurrentes()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $factures = FactureRecurrente::with('client')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $factures = FactureRecurrente::with('client')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    // Construire la réponse avec les détails des factures et les noms des clients
    $response = [];
    foreach ($factures as $facture) {
        $response[] = [
            'periode' => $facture->periode,
            'nombre_periode' => $facture->nombre_periode,
            'date_debut' => $facture->date_debut,
            'client_id' => $facture->client_id,
            'note_interne' => $facture->note_interne,
            'commentaire' => $facture->commentaire,
            'type_reccurente' => $facture->type_reccurente,
            'active_Stock' => $facture->active_Stock,
            'creation_automatique'=> $facture->creation_automatique,
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'nom_client' => $facture->client->nom_client,
            'prenom_client' => $facture->client->prenom_client,
        ];
    }
    
    return response()->json(['factures' => $response]);
    }
    
}
