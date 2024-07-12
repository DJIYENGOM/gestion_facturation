<?php

namespace App\Http\Controllers;

use App\Models\ArticleFactureAvoir;
use App\Models\Facture;
use App\Models\FactureAvoir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NumeroGeneratorService;


class FactureAvoirController extends Controller
{

    public function creerFactureAvoir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'num_factureAvoir' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'facture_id' => 'nullable|exists:factures,id',
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'date'=>'required|date',
            'prix_HT'=> 'nullable|numeric',
            'prix_TTC'=>'nullable|numeric',
            'active_Stock' => 'nullable|in:oui,non',
            'doc_externe' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

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

        $typeDocument = 'facture';
        $numFactureAvoir= NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $path = null;
        if ($request->hasFile('doc_externe')) {
            $file = $request->file('doc_externe');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('facture_avoirs', $filename, 'public');
        }

        $factureAvoir = FactureAvoir::create([
            'num_factureAvoir' => $request->num_factureAvoir ?? $numFactureAvoir,
            'facture_id' => $request->facture_id ?? null,
            'client_id' => $request->client_id,
            'titre' => $request->input('titre'),
            'description' => $request->input('description'),
            'date' => $request->date,
            'active_Stock' => $request->active_Stock ?? 'non',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'commentaire' => $request->input('commentaire'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'doc_externe' => $path,
        ]);
    
        $factureAvoir->save();
    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleFactureAvoir::create([
                'id_factureAvoir' => $factureAvoir->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }

        return response()->json(['message' => 'factureAvoir créée avec succès', 'factureAvoir' => $factureAvoir], 201);

    }

    public function listerToutesFacturesAvoirs()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $factures = FactureAvoir::with('client')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $factures = FactureAvoir::with('client')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    // Construire la réponse avec les détails des factures et les noms des clients
    $response = [];
    foreach ($factures as $facture) {
        $response[] = [
            'id' => $facture->id,
            'num_factureAvoir' => $facture->num_factureAvoir,
            'facture_id' => $facture->facture_id,
            'date' => $facture->date,
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'titre' => $facture->titre,
            'description' => $facture->description,
            'note_fact' => $facture->note_fact,
            'client_id' => $facture->client_id,
            'prenom_client' => $facture->client->prenom_client, 
            'nom_client' => $facture->client->nom_client, 
            'active_Stock' => $facture->active_Stock,
            'doc_externe' => $facture->doc_externe,
            'commentaire' => $facture->commentaire,
        ];
    }
    
    return response()->json(['factures' => $response]);
    }

    public function listerToutesFacturesSimpleAvoir()
    {
        $facturesSimples = [];
        $facturesAvoirs = [];
        
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $facturesSimples = Facture::with('client')
                ->where('archiver', 'non')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
    
            $facturesAvoirs = FactureAvoir::with('client')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $facturesSimples = Facture::with('client')
                ->where('archiver', 'non')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
    
            $facturesAvoirs = FactureAvoir::with('client')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        // Construire la réponse avec les détails combinés des factures simples et des factures d'avoirs
        $response = [];
    
        foreach ($facturesSimples as $facture) {
            $response[] = [
                'id' => $facture->id,
                'numero' => $facture->num_fact,
                'prenom_client' => $facture->client->prenom_client,
                'nom_client' => $facture->client->nom_client,
                'prix_HT' => $facture->prix_HT,
                'prix_TTC' => $facture->prix_TTC,
                'date' => $facture->date_creation,
            ];
        }
    
        foreach ($facturesAvoirs as $facture) {
            $response[] = [
                'id' => $facture->id,
                'numero' => $facture->num_factureAvoir,
                'prenom_client' => $facture->client->prenom_client,
                'nom_client' => $facture->client->nom_client,
                'prix_HT' => $facture->prix_HT,
                'prix_TTC' => $facture->prix_TTC,
                'date' => $facture->date,
                'titre'=> $facture->titre,
                'description'=> $facture->description,
            ];
        }
    
        return response()->json(['factures' => $response]);
    }
    
}