<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\ArtcleFacture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\StoreFactureRequest;
use App\Http\Requests\UpdateFactureRequest;

class FactureController extends Controller
{
    public function creerFacture(Request $request)
{
    $request->validate([
        'client_id' => 'required|exists:clients,id',
        'note_fact' => 'nullable|string',
        'reduction_facture' => 'nullable|numeric',
        'articles' => 'required|array',
        'articles.*.id_article' => 'required|exists:articles,id',
        'articles.*.quantite_article' => 'required|integer',
        'articles.*.prix_unitaire_article' => 'required|numeric',
        'articles.*.TVA_article' => 'nullable|numeric',
        'articles.*.reduction_article' => 'nullable|numeric',
    ]);

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Calcul du montant total de la facture
    $montantTotal = 0;
    foreach ($request->articles as $article) {
        $prixUnitaire = $article['prix_unitaire_article'];
        $quantite = $article['quantite_article'];
        $TVA = $article['TVA_article'] ?? 0;
        $reduction = $article['reduction_article'] ?? 0;
        $prixTotalArticle = $quantite * $prixUnitaire * (1 + $TVA / 100) * (1 - $reduction / 100);
        $montantTotal += $prixTotalArticle;
    }

    // Création de la facture avec les champs nécessaires sauf `num_fact`
    $facture = Facture::create([
        'client_id' => $request->client_id,
        'date_creation' => now(),
        'reduction_facture' => $request->input('reduction_facture', 0),
        'montant_total_fact' => $montantTotal,
        'note_fact' => $request->input('note_fact'),
        'archiver' => 'non',
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);
    $facture->num_fact = Facture::generateNumFacture($facture->id);
    $facture->save();
    // Ajouter les articles à la facture
    foreach ($request->articles as $articleData) {
        $quantite = $articleData['quantite_article'];
        $prixUnitaire = $articleData['prix_unitaire_article'];
        $TVA = $articleData['TVA_article'] ?? 0;
        $reduction = $articleData['reduction_article'] ?? 0;
        $prixTotalArticleTva = $quantite * $prixUnitaire * (1 + $TVA / 100) * (1 - $reduction / 100);
        $prixTotalArticle = $quantite * $prixUnitaire * (1 - $reduction / 100);

       $articleFacture = ArtcleFacture::create([
            'id_facture' => $facture->id,
            'id_article' => $articleData['id_article'],
            'quantite_article' => $quantite,
            'prix_unitaire_article' => $prixUnitaire,
            'TVA_article' => $TVA,
            'reduction_article' => $reduction,
            'prix_total_article' => $prixTotalArticle,
            'prix_total_tva_article' => $prixTotalArticleTva, // Si c'est pertinent
        ]);
    }

    return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture, 'articleFacture' => $articleFacture], 201);

}

public function listeArticlesFacture($id_facture)
{
    $facture = Facture::findOrFail($id_facture);
    
    // Vérifie si l'utilisateur a le droit d'accéder à la facture
    if ($facture->sousUtilisateur_id) {
        $userId = auth('apisousUtilisateur')->id();
        if ($facture->sousUtilisateur_id != $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    } elseif ($facture->user_id) {
        $userId = auth()->id();
        if ($facture->user_id != $userId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    // Récupère les articles de la facture avec leurs détails
    $articles = ArtcleFacture::where('id_facture', $id_facture)->with('article')->get();
    // Construit la réponse avec les détails des articles
    $response = [];
    foreach ($articles as $article) {
        $response[] = [
            'nom_article' => $article->article->nom_article,
            'quantite' => $article->quantite_article,
            'Montant total' => $article->prix_total_article,
            'Montant total avec TVA' => $article->prix_total_tva_article,
        ];
    }

    return response()->json(['articles' => $response]);
}
public function listerFactures()
{
$factures = Facture::with('client')->get();

// Construire la réponse avec les détails des factures et les noms des clients
$response = [];
foreach ($factures as $facture) {
    $response[] = [
        'id' => $facture->id,
        'num_fact' => $facture->num_fact,
        'date_creation' => $facture->date_creation,
        'montant_total_fact' => $facture->montant_total_fact,
        'note_fact' => $facture->note_fact,
        'prenom client' => $facture->client->prenom_client, 
        'nom client' => $facture->client->nom_client, // Nom du client associé à la facture
    ];
}

return response()->json(['factures' => $response]);
}
}
