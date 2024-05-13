<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Promo;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function ajouterArticle(Request $request)
    {
        $request->validate([
            'nom_article' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'promo_id' => 'nullable|exists:promos,id', // Vérifie si l'ID du promo existe dans la table promos
        ]);

        // Récupérer le pourcentage de la promo associée
        $pourcentagePromo = null;
        if ($request->promo_id) {
            $promo = Promo::find($request->promo_id);
            if ($promo) {
                $pourcentagePromo = $promo->pourcentage_promo;
            }
        }

        // Calculer le prix promo en fonction du prix unitaire et du pourcentage de la promo
        $prixPromo = null;
        if ($pourcentagePromo !== null) {
            $prixPromo = $request->prix_unitaire * $pourcentagePromo;
        }

        // Créer un nouvel article en utilisant les données de la requête
        $article = new Article();
        $article->nom_article = $request->nom_article;
        $article->description = $request->description;
        $article->prix_unitaire = $request->prix_unitaire;
        $article->prix_promo = $prixPromo;
        $article->type_article = $request->type_article;
        $article->promo_id = $request->promo_id;
        // Le sous_utilisateur_id peut être récupéré à partir de l'utilisateur authentifié
        $article->sousUtilisateur_id = auth('apisousUtilisateur')->id();
        
        // Enregistrer l'article dans la base de données
        $article->save();

        return response()->json(['message' => 'Article ajouté avec succès', 'article' => $article]);
    }

    public function modifierArticle(Request $request, $id)
    {
    $article = Article::findOrFail($id);

    $request->validate([
        'nom_article' => 'required|string|max:255',
        'description' => 'nullable|string',
        'prix_unitaire' => 'required|numeric|min:0',
        'type_article' => 'required|in:produit,service',
        'promo_id' => 'nullable|exists:promos,id',
    ]);

    // Mise à jour des champs de l'article
    $article->update([
        'nom_article' => $request->nom_article,
        'description' => $request->description,
        'prix_unitaire' => $request->prix_unitaire,
        'type_article' => $request->type_article,
        'promo_id' => $request->promo_id,
    ]);

    // Recalcul du prix promo si un promo est associé
    if ($article->promo_id) {
        $promo = Promo::find($article->promo_id);
        if ($promo) {
            $article->prix_promo = $article->prix_unitaire * $promo->pourcentage_promo;
            $article->save();
        }
    }

    return response()->json(['message' => 'Article modifié avec succès', 'article' => $article]);
    }


    public function supprimerArticle($id)
{
    $article = Article::findOrFail($id);
    $article->delete();

    return response()->json(['message' => 'Article supprimé avec succès']);
}

public function listerArticles()
{
    $articles = Article::all();
    return response()->json(['articles' => $articles]);
}

public function affecterPromoArticle(Request $request, $id)
{
    $article = Article::findOrFail($id);

    $request->validate([
        'promo_id' => 'nullable|exists:promos,id',
    ]);

    // Mettre à jour le champ promo_id de l'article
    $article->promo_id = $request->promo_id;
    $article->save();

    // Recalcul du prix promo si un promo est associé
    if ($article->promo_id) {
        $promo = Promo::find($article->promo_id);
        if ($promo) {
            $article->prix_promo = $article->prix_unitaire * $promo->pourcentage_promo;
            $article->save();
        }
    } else {
        // Si aucun promo n'est associé, le prix promo est null
        $article->prix_promo = null;
        $article->save();
    }

    return response()->json(['message' => 'Promo affectée à l\'article avec succès', 'article' => $article]);
}

}
