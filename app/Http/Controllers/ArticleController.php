<?php

namespace App\Http\Controllers;
use App\Models\Lot;
use App\Models\Promo;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Entrepot;
use App\Models\Variante;
use App\Models\AutrePrix;
use App\Models\Historique;
use Illuminate\Http\Request;
use App\Exports\ArticlesExport;
use App\Imports\ArticlesImport;
use App\Models\CompteComptable;
use App\Models\EntrepotArticle;
use App\Models\CategorieArticle;
use App\Models\NoteJustificative;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\Storage;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ArticleController extends Controller
{
    public function ajouterArticle(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;

        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
        
        $validator = Validator::make($request->all(), [
            'nom_article' => 'required|string',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'tva'=>'nullable|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'unité' => 'required|in:unite,kg,tonne,cm,l,m,m2,m3,h,jour,semaine,mois,g',
            'id_categorie_article' => 'nullable|exists:categorie_articles,id',
            'id_comptable' => 'nullable|exists:compte_comptables,id',
            'promo_id' => 'nullable|exists:promos,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'quantite' => 'nullable|numeric|min:0',
            'quantite_alert' => 'nullable|numeric|min:0',
            'doc_externe' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'num_article' => 'nullable|string|max:255',
            'code_barre' => 'nullable|string|max:255',
            'autres_prix' => 'nullable|array',
            'autres_prix.*.titrePrix' => 'nullable|string|max:255',
            'autres_prix.*.montant' => 'nullable|numeric|min:0',
            'autres_prix.*.tva' => 'nullable|numeric|min:0|max:100',
            'active_Stock' => 'nullable|in:oui,non',
            'variantes' => 'nullable|array',
            'variantes.*.nomVariante' => 'nullable|string|max:255',
            'variantes.*.quantiteVariante' => 'nullable|integer|min:0',
            'lots' => 'nullable|array',
            'lots.*.nomLot' => 'nullable|string|max:255',
            'lots.*.quantiteLot' => 'nullable|integer|min:0',
            'entrepots' => 'nullable|array',
            'entrepots.*.entrepot_id' => 'nullable|exists:entrepots,id',
            'entrepots.*.quantiteArt_entrepot' => 'nullable|integer|min:0',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

    
        $doc_externe = $request->hasFile('doc_externe') ? $request->file('doc_externe')->store('file', 'public') : null;
    
        $promo = $request->promo_id ? Promo::find($request->promo_id) : null;
        $pourcentagePromo = $promo ? $promo->pourcentage_promo : null;
        $prixPromo = $pourcentagePromo !== null ? $request->prix_unitaire * (1 - $pourcentagePromo / 100) : null;
        $prixTva = $request->tva !== null ? $request->prix_unitaire * (1 + $request->tva / 100) : null;
    
        $id_comptable = $request->id_comptable;
        if (!$id_comptable) {
            $compteNom = $request->type_article === 'produit' ? 'Ventes de marchandises' : 'Prestations de services';
            $compte = CompteComptable::where('nom_compte_comptable', $compteNom)->first();
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        }
    
        $typeDocument = $request->type_article == 'produit' ? 'produit' : 'service';
        $numArticle = NumeroGeneratorService::genererNumero($user_id, $typeDocument);

        //dd($numArticle);

        $article = new Article();
        $article->num_article = $request->num_article ?? $numArticle;
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
        $article->active_Stock = $request->active_Stock ?? 'non';
        $article->quantite_disponible = $request->quantite ?? 0;
        $article->benefice = $request->prix_unitaire - $request->prix_achat;
        $article->benefice_promo = $prixPromo ? $prixPromo - $request->prix_achat : null;
    
        $article->save();
        NumeroGeneratorService::incrementerCompteur($user_id, $typeDocument);

        if($typeDocument == 'produit'){
            Historique::create([
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'message' => 'Des Produits ont été Ajoutés',
                'is_article' => $article->id
            ]);
        }else{
            Historique::create([
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'message' => 'Des Services ont été Ajoutés',
                'is_article' => $article->id
            ]);
        }

    
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

        // Générer un num_stock unique pour chaque article
        $numStock = $article->num_article;
    
        if ($request->active_Stock === 'oui') {
            if ($request->has('lots')) {
                foreach ($request->lots as $lot) {
                    $stock = new Stock();
                   $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $lot['nomLot'];
                    $stock->disponible_avant = 0;
                    $stock->modif = $lot['quantiteLot'];
                    $stock->disponible_apres = $lot['quantiteLot'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if ($request->has('entrepots')) {
                foreach ($request->entrepots as $entrepot) {
                    $Entrepot=Entrepot::Where('id', $entrepot['entrepot_id']);

                    $nomEntrepot = $Entrepot->first()->nomEntrepot;

                    $stock = new Stock();
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $nomEntrepot;
                    $stock->disponible_avant = 0;
                    $stock->modif = $entrepot['quantiteArt_entrepot'];
                    $stock->disponible_apres = $entrepot['quantiteArt_entrepot'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if ($request->has('variantes')) {
                foreach($request->variantes as $variante) {
                    $stock = new Stock();
                   $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $variante['nomVariante'];
                    $stock->disponible_avant = 0;
                    $stock->modif = $variante['quantiteVariante'];
                    $stock->disponible_apres = $variante['quantiteVariante'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if (!$request->has('lots') && !$request->has('variantes') && !$request->has('entrepots')) {
                $stock = new Stock();
               $stock->date_stock = now()->format('Y-m-d');
                $stock->num_stock = $numStock; 
                $stock->libelle = $article->nom_article. ' - original';
                $stock->disponible_avant = 0;
                $stock->modif = $article->quantite ?? 0;
                $stock->disponible_apres = $article->quantite ?? 0;
                $stock->article_id = $article->id;
                $stock->facture_id = null;
                $stock->bonCommande_id = null;
                $stock->livraison_id = null;
                $stock->sousUtilisateur_id = $sousUtilisateur_id;
                $stock->user_id = $user_id;
                $stock->save();
            }

        }

    if($article->quantite!= null && $article->quantite_alert!= null){

        if($article->quantite== $article->quantite_alert || $article->quantite < $article->quantite_alert)
        {
            Notification::create([
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'id_article' => $article->id,
                'message' => 'La quantité des produits (' .$article->nom_article . ') atteind la quantité d\'alerte.',
            ]);
        }
    }
        return response()->json(['message' => 'Article ajouté avec succès', 'article' => $article]);
    }
    

    public function modifierArticle(Request $request, $id)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'nom_article' => 'required|string',
            'description' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'tva'=>'nullable|numeric|min:0',
            'type_article' => 'required|in:produit,service',
            'unité' => 'required|in:unite,kg,tonne,cm,l,m,m2,m3,h,jour,semaine,mois,g',
            'categorie_article_id' => 'nullable|exists:categorie_articles,id',
            'id_comptable' => 'nullable|exists:compte_comptables,id',
            'promo_id' => 'nullable|exists:promos,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'quantite' => 'nullable|numeric|min:0',
            'quantite_alert' => 'nullable|numeric|min:0',
            'doc_externe' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'num_article' => 'nullable|string|max:255',
            'code_barre' => 'nullable|string|max:255',
            'autres_prix' => 'nullable|array',
            'autres_prix.*.titrePrix' => 'nullable|string|max:255',
            'autres_prix.*.montant' => 'nullable|numeric|min:0',
            'autres_prix.*.tva' => 'nullable|numeric|min:0|max:100',
            'active_Stock' => 'nullable|in:oui,non',
            'variantes' => 'nullable|array',
            'variantes.*.nomVariante' => 'nullable|string|max:255',
            'variantes.*.quantiteVariante' => 'nullable|integer|min:0',
            'lots' => 'nullable|array',
            'lots.*.nomLot' => 'nullable|string|max:255',
            'lots.*.quantiteLot' => 'nullable|integer|min:0',
            'entrepots' => 'nullable|array',
            'entrepots.*.entrepot_id' => 'nullable|exists:entrepots,id',
            'entrepots.*.quantiteArt_entrepot' => 'nullable|integer|min:0',
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
        $article->code_barre = $request->code_barre;
        $article->prix_achat = $request->prix_achat;
        $article->quantite = $request->quantite;
        $article->quantite_alert = $request->quantite_alert;
        $article->benefice = $request->prix_unitaire - $request->prix_achat;
        $article->benefice_promo = $prixPromo ? $prixPromo - $request->prix_achat : null;
    
        $article->update();

        if($article->type_article == 'produit'){
            Historique::create([
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'message' => 'Des Produits ont été Modifiés',
                'is_article' => $article->id
            ]);
        }else{
            Historique::create([
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $user_id,
                'message' => 'Des Services ont été Modifiés',
                'is_article' => $article->id
            ]);
        }
    
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
                    'quantiteVariante' => $variante['quantiteVariante'],
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
                    'entrepot_id' => $entrepot['entrepot_id'],
                    'quantiteArt_entrepot' => $entrepot['quantiteArt_entrepot'],
                ]);
                $entrepotArticle->save();
            }
        }

        $numStock = $article->num_article;
    
        if ($request->active_Stock === 'oui') {
            if ($request->has('lots')) {
                foreach ($request->lots as $lot) {
                    $stock = new Stock();
                   $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $lot['nomLot'];
                    $stock->disponible_avant = 0;
                    $stock->modif = $lot['quantiteLot'];
                    $stock->disponible_apres = $lot['quantiteLot'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if ($request->has('entrepots')) {
                foreach ($request->entrepots as $entrepot) {
                    $Entrepot=Entrepot::Where('id', $entrepot['entrepot_id']);

                    $nomEntrepot = $Entrepot->first()->nomEntrepot;

                    $stock = new Stock();
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $nomEntrepot;
                    $stock->disponible_avant = 0;
                    $stock->modif = $entrepot['quantiteArt_entrepot'];
                    $stock->disponible_apres = $entrepot['quantiteArt_entrepot'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if ($request->has('variantes')) {
                foreach($request->variantes as $variante) {
                    $stock = new Stock();
                   $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock;
                    $stock->libelle = $article->nom_article . ' - ' . $variante['nomVariante'];
                    $stock->disponible_avant = 0;
                    $stock->modif = $variante['quantiteVariante'];
                    $stock->disponible_apres = $variante['quantiteVariante'];
                    $stock->article_id = $article->id;
                    $stock->facture_id = null;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateur_id;
                    $stock->user_id = $user_id;
                    $stock->save();
                }
            }
    
            if (!$request->has('lots') && !$request->has('variantes') && !$request->has('entrepots')) {
                $stock = new Stock();
               $stock->date_stock = now()->format('Y-m-d');
                $stock->num_stock = $numStock; 
                $stock->libelle = $article->nom_article. ' - original';
                $stock->disponible_avant = 0;
                $stock->modif = $article->quantite ?? 0;
                $stock->disponible_apres = $article->quantite ?? 0;
                $stock->article_id = $article->id;
                $stock->facture_id = null;
                $stock->bonCommande_id = null;
                $stock->livraison_id = null;
                $stock->sousUtilisateur_id = $sousUtilisateur_id;
                $stock->user_id = $user_id;
                $stock->save();
            }

        }

        if($article->quantite!= null && $article->quantite_alert!= null){

            if($article->quantite== $article->quantite_alert || $article->quantite < $article->quantite_alert)
            {
                Notification::create([
                    'sousUtilisateur_id' => $sousUtilisateur_id,
                    'user_id' => $user_id,
                    'id_article' => $article->id,
                    'message' => 'La quantité des produits (' .$article->nom_article . ') atteind la quantité d\'alerte.',
                ]);
            }
        }
    
        return response()->json(['message' => 'Article modifié avec succès', 'article' => $article]);
    }
    


public function supprimerArticle($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
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
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}


public function listerArticles()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $articles = Article::with('categorieArticle', 'CompteComptable','Stocks', 'EntrepotArt', 'lot', 'autrePrix', 'variante')
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $articles = Article::with('categorieArticle', 'CompteComptable','Stocks', 'EntrepotArt', 'lot', 'autrePrix', 'variante')
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $articlesArray = $articles->map(function ($article) {
        $articleArray = $article->toArray();
        $articleArray['quantite_disponible'] = optional($article->Stocks->last())->disponible_apres;
        $articleArray['nom_categorie'] = optional($article->categorieArticle)->nom_categorie_article;
        $articleArray['nom_comptable'] = optional($article->CompteComptable)->nom_compte_comptable;
        return $articleArray;
    });
    return response()->json($articlesArray);
}


public function affecterPromoArticle(Request $request, $id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
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
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

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
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
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
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

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
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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

public function importArticle(Request $request)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:xlsx,xls'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $compte = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')->first();
    if ($compte) {
        $id_comptable = $compte->id;
    } else {
        $id_comptable = null;
    }

    $categorie= CategorieArticle::where('nom_categorie_article', 'produit')->first();
    if ($categorie) {
        $id_categorie_article = $categorie->id;
    } else {
        $id_categorie_article = null;
    }
    // Traitement du fichier avec capture des erreurs
    try {
        Excel::import(new ArticlesImport($user_id, $sousUtilisateur_id, $id_comptable, $id_categorie_article), $request->file('file'));
        return response()->json(['message' => 'Articles imported successfully']);
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();

        foreach ($failures as $failure) {
            Log::error('Row ' . $failure->row() . ' has errors: ' . json_encode($failure->errors()));
        }

        return response()->json(['errors' => $failures], 422);
    }
}



public function exportArticles()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'num_article');
    $sheet->setCellValue('B1', 'nom_article');
    $sheet->setCellValue('C1', 'quantite');
    $sheet->setCellValue('D1', 'prix_achat');
    $sheet->setCellValue('E1', 'unité');
    $sheet->setCellValue('F1', 'prix_unitaire');
    $sheet->setCellValue('G1', 'tva');
    $sheet->setCellValue('H1', 'description');
    $sheet->setCellValue('I1', 'Date de création');
    $sheet->setCellValue('J1', 'Date de modification');

    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $articles = Article::with(['lot', 'entrepotArt.entrepot'])
        ->where('type_article', 'produit')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $articles = Article::with(['lot', 'entrepotArt.entrepot'])
        ->where('type_article', 'produit')
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                      $query->where('id_user', $userId);
                  });
        })
        ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Remplir les données
    $row = 2;
    foreach ($articles as $article) {
        $nomArticle = $article->nom_article;

        // Ajouter le nom des lots s'ils existent
        if ($article->lot->isNotEmpty()) {
            $nomLots = $article->lot->pluck('nomLot')->implode(', ');
            $nomArticle .= ' -' . $nomLots;
        }

        // Ajouter le nom des entrepôts s'ils existent
        if ($article->entrepotArt->isNotEmpty()) {
            $nomEntrepots = $article->entrepotArt->map(function ($entrepotArt) {
                return $entrepotArt->entrepot ? $entrepotArt->entrepot->nomEntrepot : '';
            })->filter()->implode(', ');
            $nomArticle .= ' -' . $nomEntrepots;
        }

        $sheet->setCellValue('A' . $row, $article->num_article);
        $sheet->setCellValue('B' . $row, $nomArticle);
        $sheet->setCellValue('C' . $row, $article->quantite);
        $sheet->setCellValue('D' . $row, $article->prix_achat);
        $sheet->setCellValue('E' . $row, $article->unité);
        $sheet->setCellValue('F' . $row, $article->prix_unitaire);
        $sheet->setCellValue('G' . $row, $article->tva);
        $sheet->setCellValue('H' . $row, $article->description);
        $sheet->setCellValue('I' . $row, $article->created_at);
        $sheet->setCellValue('J' . $row, $article->updated_at);
        $row++;
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Produits.xlsx';

    // Définir les en-têtes HTTP pour le téléchargement
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Générer le fichier et l'envoyer au navigateur
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


public function exportServices()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Code');
    $sheet->setCellValue('B1', 'Libelle');
    $sheet->setCellValue('C1', 'Description');
    $sheet->setCellValue('D1', 'TVA');
    $sheet->setCellValue('E1', 'prix_vente_HT');
    $sheet->setCellValue('F1', 'prix_vente_TTC');
    $sheet->setCellValue('G1', 'Date de création');
    $sheet->setCellValue('H1', 'Dernière modification');



    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $articles = Article::where('type_article', 'service')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $articles = Article::where('type_article', 'service')
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                      $query->where('id_user', $userId);
                  });
        })
        ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Remplir les données
    $row = 2;
    foreach ($articles as $article) {
        $nomArticle = $article->nom_article;

        $sheet->setCellValue('A' . $row, $article->num_article);
        $sheet->setCellValue('B' . $row, $nomArticle);
        $sheet->setCellValue('C' . $row, $article->description);
        $sheet->setCellValue('D' . $row, $article->tva);
        $sheet->setCellValue('E' . $row, $article->prix_unitaire);
        $sheet->setCellValue('F' . $row, $article->prix_tva);
        $sheet->setCellValue('G' . $row, $article->created_at);
        $sheet->setCellValue('H' . $row, $article->updated_at);

        $row++;
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Services.xlsx';

    // Définir les en-têtes HTTP pour le téléchargement
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Générer le fichier et l'envoyer au navigateur
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}


}
