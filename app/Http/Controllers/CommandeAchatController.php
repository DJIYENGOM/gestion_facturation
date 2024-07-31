<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use Illuminate\Http\Request;
use App\Models\CommandeAchat;
use App\Models\ArticleCommandeAchat;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;

class CommandeAchatController extends Controller
{
    public function creerCommandeAchat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activation'=> 'required|boolean',
            'date_commandeAchat'=>'required|date',
            'date_livraison'=>'nullable|date',
            'total_TTC'=>'nullable|numeric',
            'titre'=>'nullable|string',
            'description'=>'nullable|string',
            'active_Stock'=> 'nullable|boolean',
            'statut_commande'=> 'nullable|in:commander,recu,annuler,brouillon',
            'fournisseur_id'=> 'nullable|exists:fournisseurs,id',
            'depense_id'=> 'nullable|exists:depenses,id',
            'commentaire'=> 'nullable|string',
            'note_interne' => 'nullable|string',
            'doc_interne' => 'nullable|string',
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
            $userId = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $typeDocument = 'commande_achat';
        $numBonCommande = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $commandeData = [
            'num_commandeAchat' => $numBonCommande,
            'activation' => $request->activation,
            'date_commandeAchat' => $request->date_commandeAchat,
            'date_livraison' => $request->date_livraison,
            'total_TTC' => $request->total_TTC,
            'titre' => $request->titre,
            'description' => $request->description,
            'date_paiement' => $request->date_paiement,
            'statut_commande' => $request->statut_commande ?? 'commander',
            'fournisseur_id' => $request->fournisseur_id,
            'depense_id' => $request->depense_id,
            'commentaire' => $request->commentaire,
            'note_interne' => $request->note_interne,
            'doc_interne' => $request->doc_interne,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ];
    
        if ($request->depense_id) {
            $depense = Depense::find($request->depense_id);
            if ($depense && $depense->statut_depense == 'payer') {
                $commandeData['date_paiement'] = $depense->date_paiement_depense;
            }
        }
    
        $commande = CommandeAchat::create($commandeData);
    
        // Ajouter les articles à la commande
        foreach ($request->articles as $articleData) {
            ArticleCommandeAchat::create([
                'id_CommandeAchat' => $commande->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $articleData['quantite_article'],
                'prix_unitaire_article' => $articleData['prix_unitaire_article'],
                'TVA_article' => $articleData['TVA_article'] ?? 0,
                'reduction_article' => $articleData['reduction_article'] ?? 0,
                'prix_total_article' => $articleData['prix_total_article'],
                'prix_total_tva_article' => $articleData['prix_total_tva_article'],
            ]);
        }
    
        return response()->json(['message' => 'Commande créée avec succès', 'commande' => $commande], 201);
    }
    
}
