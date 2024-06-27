<?php

namespace App\Http\Controllers;

use App\Models\ArticleBonCommande;
use App\Models\BonCommande;
use App\Models\Echeance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BonCommandeController extends Controller
{
    public function creerBonCommande(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_commande' => 'nullable|string',
            'reduction_commande' => 'nullable|numeric',
            'date_commande'=>'required|date',
            'date_limite_commande'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'statut_commande'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',
            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'required|date',
            'echeances.*.montant_echeance' => 'required|numeric|min:0',

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
        $commande = BonCommande::create([
            'client_id' => $request->client_id,
            'date_commande' => $request->date_bonCommande,
            'date_limite_commande' => $request->date_limite_commande,
            'reduction_commande' => $request->input('reduction_facture', 0),
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_commande' => $request->input('note_commande'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_commande' => $request->statut_commande ?? 'en_attente',
        ]);
    
        $commande->num_commande = BonCommande::generateNumBoncommande($commande->id);
        $commande->save();
    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleBonCommande::create([
                'id_BonCommande' => $commande->id,
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
                    'id_BonCommande' => $commande->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }

        return response()->json(['message' => 'commande créée avec succès', 'commande' => $commande], 201);

    }
}
