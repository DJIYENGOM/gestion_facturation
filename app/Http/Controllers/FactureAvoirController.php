<?php

namespace App\Http\Controllers;

use App\Models\ArticleFactureAvoir;
use App\Models\FactureAvoir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NumeroGeneratorService;


class FactureAvoirController extends Controller
{

    public function creerFactureAvoir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'facture_id' => 'nullable|exists:factures,id',
            'commentaire' => 'nullable|string',
            'date'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'active_Stock' => 'nullable|in:oui,non',
            
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
    
        $factureAvoir = FactureAvoir::create([
            'num_factureAvoir' => $numFactureAvoir,
            'facture_id' => $request->facture_id ?? null,
            'client_id' => $request->client_id,
            'date' => $request->date,
            'active_Stock' => $request->active_Stock ?? 'non',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'commentaire' => $request->input('commentaire'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
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
                'id_FactureAvoir' => $factureAvoir->id,
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
}