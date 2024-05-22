<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Promo;
use Illuminate\Http\Request;
use App\Models\NoteJustificative;

class ArticleController extends Controller
{
    public function ajouterArticle(Request $request)
    {
        
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
        $validator=Validator::make($request->all(),[
            'nom_article' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'categorie_article_id' => 'nullable|exists:categorie_articles,id',
            'promo_id' => 'nullable|exists:promos,id', // Vérifie si l'ID du promo existe dans la table promos
            'prix_achat' => 'nullable|numeric|min:0',
            'quantite' => 'nullable|numeric|min:0',
            'quantite_alert' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],422);
        }

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
        $article->sousUtilisateur_id = $sousUtilisateur_id;
        $article->user_id = $user_id;
        $article->id_categorie_article = $request->id_categorie_article;

        $article->prix_achat = $request->prix_achat;
        $article->quantite = $request->quantite;
        $article->quantite_alert = $request->quantite_alert;
        $article->benefice=$request->prix_unitaire - $request->prix_achat;
        $article->benefice_promo = $prixPromo - $request->prix_achat;
        $article->save();

        return response()->json(['message' => 'Article ajouté avec succès', 'article' => $article]);
    }

    public function modifierArticle(Request $request, $id)
    {
        
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $article = Article::findOrFail($id);

    $validator=Validator::make($request->all(),[
        'nom_article' => 'required|string|max:255',
        'description' => 'nullable|string',
        'prix_unitaire' => 'required|numeric|min:0',
        'type_article' => 'required|in:produit,service',
        'promo_id' => 'nullable|exists:promos,id',
        'categorie_article_id' => 'nullable|exists:categorie_articles,id',

        'prix_achat' => 'nullable|numeric|min:0',
        'quantite' => 'nullable|numeric|min:0',
        'quantite_alert' => 'nullable|numeric|min:0',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors(),
        ],422);
    }

    $article->update([
        'nom_article' => $request->nom_article,
        'description' => $request->description,
        'prix_unitaire' => $request->prix_unitaire,
        'type_article' => $request->type_article,
        'promo_id' => $request->promo_id,
        'id_categorie_article' => $request->id_categorie_article,

        'prix_achat' => $request->prix_achat,
        'quantite' => $request->quantite,
        'quantite_alert' => $request->quantite_alert,
        'benefice'=>$request->prix_unitaire - $request->prix_achat,
        'sousUtilisateur_id' => $sousUtilisateur_id,
        'user_id' => $user_id,
    ]);

    // Recalcul du prix promo si un promo est associé
    if ($article->promo_id) {
        $promo = Promo::find($article->promo_id);
        if ($promo) {
            $article->prix_promo = $article->prix_unitaire * $promo->pourcentage_promo;
            $article->benefice_promo = $article->prix_promo - $article->prix_achat;
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
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $articles = Article::with('categorieArticle')
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $articles = Article::with('categorieArticle')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $articles = $articles->map(function ($article) {
        return [
            'id' => $article->id,
            'nom_article' => $article->nom_article,
            'description' => $article->description,
            'prix_unitaire' => $article->prix_unitaire,
            'quantite' => $article->quantite,
            'prix_achat' => $article->prix_achat,
            'benefice' => $article->benefice,
            'prix_promo' => $article->prix_promo,
            'benefice_promo' => $article->benefice_promo,
            'quantite_alert' => $article->quantite_alert,
            'type_article' => $article->type_article,
            'promo_id' => $article->promo_id,
            'sousUtilisateur_id' => $article->sousUtilisateur_id,
            'user_id' => $article->user_id,
            'id_categorie_article' => $article->id_categorie_article,
            'nom_categorie' => $article->categorieArticle->nom_categorie_article ?? null,
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
        ];
    });
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
            $article->benefice_promo = $article->prix_promo - $article->prix_achat;
            $article->save();
        }
    } else {
        // Si aucun promo n'est associé, le prix promo est null
        $article->prix_promo = null;
        $article->save();
    }

    return response()->json(['message' => 'Promo affectée à l\'article avec succès', 'article' => $article]);
}


public function affecterCategorieArticle(Request $request, $id)
{
    $article = Article::findOrFail($id);

    $request->validate([
        'id_categorie_article' => 'required|exists:categorie_articles,id',
    ]);

    // Mettre à jour le champ id_categorie_article de l'article
    $article->id_categorie_article = $request->id_categorie_article;
    $article->save();

    return response()->json(['message' => 'categorie affectée à l\'article avec succès', 'article' => $article]);
}


public function modifierQuantite(Request $request, $id)
{
    $request->validate([
        'quantite' => 'required|integer',
        'note' => 'required|string|max:255',
    ]);

    $article = Article::findOrFail($id);

    // Authentification du sous-utilisateur ou de l'utilisateur
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Modifier la quantité de l'article
    $article->quantite = $request->input('quantite');
    $article->save();

    // Ajouter la note justificative
    NoteJustificative::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'article_id' => $article->id,
        'note' => $request->input('note'),
    ]);

    return response()->json(['message' => 'Quantité modifiée avec succès', 'article' => $article]);
}

}
