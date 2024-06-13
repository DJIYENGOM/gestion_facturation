<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use App\Models\Article;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Promo;
use Illuminate\Http\Request;
use App\Models\NoteJustificative;
use App\Models\CompteComptable;
use App\Models\Lot;
use App\Models\Variante;
use App\Models\AutrePrix;
use App\Models\EntrepotArticle;

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
    
        $validator = Validator::make($request->all(), [
            'nom_article' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'tva'=>'nullable|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'unité' => 'required|in:unite,kg,tonne,cm,l,m,m2,m3,h,jour,semaine,mois',
            'categorie_article_id' => 'nullable|exists:categorie_articles,id',
            'id_comptable' => 'nullable|exists:compte_comptables,id',
            'promo_id' => 'nullable|exists:promos,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'quantite' => 'nullable|numeric|min:0',
            'quantite_alert' => 'nullable|numeric|min:0',
            'doc_externe' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'num_article' => 'nullable|string|unique:articles,num_article',
            'autres_prix' => 'nullable|array',
            'autres_prix.*.titrePrix' => 'required_with:autres_prix|string|max:255',
            'autres_prix.*.montant' => 'required_with:autres_prix|numeric|min:0',
            'autres_prix.*.tva' => 'nullable|numeric|min:0|max:100',
            'variantes' => 'nullable|array',
            'variantes.*.nomVariante' => 'required_with:variantes|string|max:255',
            'variantes.*.quantiteVariante' => 'required_with:variantes|integer|min:0',
            'lots' => 'nullable|array',
            'lots.*.nomLot' => 'required_with:lots|string|max:255',
            'lots.*.quantiteLot' => 'required_with:lots|integer|min:0',
            'entrepots' => 'nullable|array',
            'entrepots.*.entrepot_id' => 'required_with:entrepots|exists:entrepots,id',
            'entrepots.*.quantiteArt_entrepot' => 'required_with:entrepots|integer|min:0',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $doc_externe = null;
        if ($request->hasFile('doc_externe')) {
            $doc_externe = $request->file('doc_externe')->store('file', 'public');
        }
    
        $pourcentagePromo = null;
        if ($request->promo_id) {
            $promo = Promo::find($request->promo_id);
            if ($promo) {
                $pourcentagePromo = $promo->pourcentage_promo;
            }
        }
    
        $prixPromo = null;
        if ($pourcentagePromo !== null) {
            $prixPromo = $request->prix_unitaire * $pourcentagePromo;
        }
        $prixTva = null;
        $tva=$request->tva;
        if ($request->tva !== null) {
            $prixTva = $request->prix_unitaire * ((100 + $tva) / 100);
        }

        if (!$request->has('id_comptable')) {
            if ($request->type_article === 'produit') {
                $compte = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')
                                         ->first();
            } elseif ($request->type_article === 'service') {
                $compte = CompteComptable::where('nom_compte_comptable', 'Prestations de services')
                                         ->first();
            }
    
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        $article = new Article();
        $article->num_article = $request->num_article;
        $article->nom_article = $request->nom_article;
        $article->description = $request->description;
        $article->prix_unitaire = $request->prix_unitaire;
        $article->tva = $request->tva;
        $article->prix_tva = $prixTva;
        $article->prix_promo = $prixPromo;
        $article->type_article = $request->type_article;
        $article->promo_id = $request->promo_id;
        $article->sousUtilisateur_id = $sousUtilisateur_id;
        $article->user_id = $user_id;
        $article->id_categorie_article = $request->id_categorie_article;
        $article->id_comptable = $id_comptable;
        $article->unité = $request->unité;
        $article->doc_externe = $doc_externe;
        $article->prix_achat = $request->prix_achat;
        $article->quantite = $request->quantite;
        $article->quantite_alert = $request->quantite_alert;
        $article->benefice = $request->prix_unitaire - $request->prix_achat;
        $article->benefice_promo = $prixPromo ? $prixPromo - $request->prix_achat : null;
        
        $article->save();
     
        if ($request->has('autres_prix')) {  
            foreach ($request->autres_prix as $autre_prix) {
                $autrePrix = new AutrePrix([
                    'article_id' => $article->id,
                    'titrePrix' => $autre_prix['titrePrix'],
                    'montant' => $autre_prix['montant'],
                    'tva' => $autre_prix['tva'],
                    'montantTva' => $autre_prix['montant'] * ((100 + $autre_prix['tva']) / 100),
                ]);
                $autrePrix->save();
            }
        }
    
        if ($request->has('variantes')) {
            foreach ($request->variantes as $variante) {
                $var = new Variante([
                    'article_id' => $article->id,
                    'nomVariante' => $variante['nomVariante'],
                    'quantiteVariante' => $variante['quantiteVariante'],
                ]);
                $var->save();
            }
        }
    
        if ($request->has('lots')) {
            foreach ($request->lots as $lot) {
                $lotEntry = new Lot([
                    'article_id' => $article->id,
                    'nomLot' => $lot['nomLot'],
                    'quantiteLot' => $lot['quantiteLot'],
                ]);
                $lotEntry->save();
            }
        }
    
        if ($request->has('entrepots')) {
            foreach ($request->entrepots as $entrepot) {
                $entrepotArticle = new EntrepotArticle([
                    'article_id' => $article->id,
                    'entrepot_id' => $entrepot['entrepot_id'],
                    'quantiteArt_entrepot' => $entrepot['quantiteArt_entrepot'],
                ]);
                $entrepotArticle->save();
            }
        }
    
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
    
        $validator = Validator::make($request->all(), [
            'nom_article' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'tva' => 'nullable|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'unité' => 'required|in:unite,kg,tonne,cm,l,m,m2,m3,h,jour,semaine,mois',
            'categorie_article_id' => 'nullable|exists:categorie_articles,id',
            'id_comptable' => 'nullable|exists:compte_comptables,id',
            'promo_id' => 'nullable|exists:promos,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'quantite' => 'nullable|numeric|min:0',
            'quantite_alert' => 'nullable|numeric|min:0',
            'doc_externe' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'num_article' => 'required|string|unique:articles,num_article,' . $id,
            'autres_prix' => 'nullable|array',
            'autres_prix.*.titrePrix' => 'required_with:autres_prix|string|max:255',
            'autres_prix.*.montant' => 'required_with:autres_prix|numeric|min:0',
            'autres_prix.*.tva' => 'nullable|numeric|min:0|max:100',
            'variantes' => 'nullable|array',
            'variantes.*.nomVariante' => 'required_with:variantes|string|max:255',
            'variantes.*.quantite' => 'required_with:variantes|integer|min:0',
            'lots' => 'nullable|array',
            'lots.*.nomLot' => 'required_with:lots|string|max:255',
            'lots.*.quantiteLot' => 'required_with:lots|integer|min:0',
            'entrepots' => 'nullable|array',
            'entrepots.*.id_entrepot' => 'required_with:entrepots|exists:entrepots,id',
            'entrepots.*.quantiteArt_entrepot' => 'required_with:entrepots|integer|min:0',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $article = Article::findOrFail($id);
    
        $doc_externe = $article->doc_externe;
        if ($request->hasFile('doc_externe')) {
            if ($doc_externe) {
                Storage::disk('public')->delete($doc_externe);
            }
            $doc_externe = $request->file('doc_externe')->store('file', 'public');
        }
    
        $pourcentagePromo = null;
        if ($request->promo_id) {
            $promo = Promo::find($request->promo_id);
            if ($promo) {
                $pourcentagePromo = $promo->pourcentage_promo;
            }
        }
    
        $prixPromo = null;
        if ($pourcentagePromo !== null) {
            $prixPromo = $request->prix_unitaire * $pourcentagePromo;
        }
    
        if (!$request->has('id_comptable')) {
            if ($request->type_article === 'produit') {
                $compte = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')->first();
            } elseif ($request->type_article === 'service') {
                $compte = CompteComptable::where('nom_compte_comptable', 'Prestations de services')->first();
            }
    
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        $article->num_article = $request->num_article;
        $article->nom_article = $request->nom_article;
        $article->description = $request->description;
        $article->prix_unitaire = $request->prix_unitaire;
        $article->tva = $request->tva;
        $article->prix_promo = $prixPromo;
        $article->type_article = $request->type_article;
        $article->promo_id = $request->promo_id;
        $article->sousUtilisateur_id = $sousUtilisateur_id;
        $article->user_id = $user_id;
        $article->id_categorie_article = $request->id_categorie_article;
        $article->id_comptable = $id_comptable;
        $article->unité = $request->unité;
        $article->doc_externe = $doc_externe;
        $article->prix_achat = $request->prix_achat;
        $article->quantite = $request->quantite;
        $article->quantite_alert = $request->quantite_alert;
        $article->benefice = $request->prix_unitaire - $request->prix_achat;
        $article->benefice_promo = $prixPromo ? $prixPromo - $request->prix_achat : null;
    
        $article->save();
    
        // Gérer les autres prix
        if ($request->has('autres_prix')) {
            AutrePrix::where('article_id', $id)->delete();
            foreach ($request->autres_prix as $autre_prix) {
                $autrePrix = new AutrePrix([
                    'article_id' => $article->id,
                    'titrePrix' => $autre_prix['titrePrix'],
                    'montant' => $autre_prix['montant'],
                    'tva' => $autre_prix['tva'],
                ]);
                $autrePrix->save();
            }
        }
    
        // Gérer les variantes
        if ($request->has('variantes')) {
            Variante::where('article_id', $id)->delete();
            foreach ($request->variantes as $variante) {
                $var = new Variante([
                    'article_id' => $article->id,
                    'nomVariante' => $variante['nomVariante'],
                    'quantite' => $variante['quantite'],
                ]);
                $var->save();
            }
        }
    
        // Gérer les lots
        if ($request->has('lots')) {
            Lot::where('article_id', $id)->delete();
            foreach ($request->lots as $lot) {
                $lotEntry = new Lot([
                    'article_id' => $article->id,
                    'nomLot' => $lot['nomLot'],
                    'quantiteLot' => $lot['quantiteLot'],
                ]);
                $lotEntry->save();
            }
        }
    
        // Gérer les entrepôts
        if ($request->has('entrepots')) {
            EntrepotArticle::where('article_id', $id)->delete();
            foreach ($request->entrepots as $entrepot) {
                $entrepotArticle = new EntrepotArticle([
                    'article_id' => $article->id,
                    'entrepot_id' => $entrepot['id_entrepot'],
                    'quantiteArt_entrepot' => $entrepot['quantiteArt_entrepot'],
                ]);
                $entrepotArticle->save();
            }
        }
    
        return response()->json(['message' => 'Article modifié avec succès', 'article' => $article]);
    }
    


public function supprimerArticle($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $Article = Article::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
        if ($Article)
            {
                $Article->delete();
            return response()->json(['message' => 'Article supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet Article'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $Article = Article::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();

            if ($Article)
            {
                $Article->delete();
                return response()->json(['message' => 'Article supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet Article'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}


public function listerArticles()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $articles = Article::with('categorieArticle', 'CompteComptable')
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $articles = Article::with('categorieArticle', 'CompteComptable')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $articlesArray = $articles->map(function ($article) {
        $articleArray = $article->toArray();
        $articleArray['nom_categorie'] = optional($article->categorieArticle)->nom_categorie;
        $articleArray['nom_comptable'] = optional($article->CompteComptable)->nom_compte_comptable;
        return $articleArray;
    });
    return response()->json($articlesArray);
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

public function affecterComptableArticle(Request $request, $id)
{
    $article = Article::findOrFail($id);

    $request->validate([
        'id_comptable' => 'nullable|exists:compte_comptables,id',
    ]);

    // Si l'utilisateur ne fournit pas un id_comptable
    if (!$request->has('id_comptable')) {
        if ($article->type_article === 'produit') {
            // Rechercher l'ID du compte comptable 'Ventes de marchandises' pour l'utilisateur courant
            $compte = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')
                                     ->where('user_id', auth()->id())
                                     ->first();
        } elseif ($article->type_article === 'service') {
            // Rechercher l'ID du compte comptable 'Prestations de services' pour l'utilisateur courant
            $compte = CompteComptable::where('nom_compte_comptable', 'Prestations de services')
                                     ->where('user_id', auth()->id())
                                     ->first();
        }

        // Assigner l'ID du compte comptable par défaut trouvé
        if ($compte) {
            $article->id_comptable = $compte->id;
        } else {
            return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
        }
    } else {
        // Si l'utilisateur a fourni un id_comptable, l'utiliser
        $article->id_comptable = $request->id_comptable;
    }

    $article->save();

    return response()->json(['message' => 'Comptable affecté à l\'article avec succès', 'article' => $article]);
}

public function listerLotsArticle($articleId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

             Article::findOrFail($articleId)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
            $lots = Lot::where('article_id', $articleId)->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

             Article::findOrFail($articleId)->with('categorieArticle', 'CompteComptable')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });
            $lots = Lot::where('article_id', $articleId)->get();

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return response()->json(['lots' => $lots]);
}

public function listerAutrePrixArticle($articleId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

             Article::findOrFail($articleId)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
            $prixAlternatifs = AutrePrix::where('article_id', $articleId)->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

             Article::findOrFail($articleId)->with('categorieArticle', 'CompteComptable')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });
            $prixAlternatifs = AutrePrix::where('article_id', $articleId)->get();

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    return response()->json(['autre_prix' => $prixAlternatifs]);
}

public function listerEntrepotsArticle($articleId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

             Article::findOrFail($articleId)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
            $entrepots = EntrepotArticle::where('article_id', $articleId)->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

             Article::findOrFail($articleId)->with('categorieArticle', 'CompteComptable')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });
            $entrepots = EntrepotArticle::where('article_id', $articleId)->get();

    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    return response()->json(['entrepots' => $entrepots]);
}

public function afficherArticleAvecPrix($articleId)
{

    $article = Article::find($articleId); //recuperer l'article pour obtenir son prix unitaire

    if (!$article) {
        return response()->json(['error' => 'Article non trouvé'], 404);
    }
    // Récupérer les autres prix de l'article
    $autresPrix = AutrePrix::where('article_id', $articleId)->get();

    // Formater les données à retourner
    $response = [
        'article' => [
            'id' => $article->id,
            'nom_article' => $article->nom_article,
            'prix_unitaire (HT)' => $article->prix_unitaire,
            'prix_promo' => $article->prixPromo,
            'prix_Tva (HTT)' => $article->prix_Tva,

        ],
        'autres_prix' => $autresPrix,

    ];

    return response()->json($response);
}

}
