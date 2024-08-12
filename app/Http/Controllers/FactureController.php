<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\FactureRecurrente;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;



class FactureController extends Controller
{
    
    public function creerFacture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'num_facture'=>'nullable|string',
            'note_fact' => 'nullable|string',
            'reduction_facture' => 'nullable|numeric',
            'active_Stock'=> 'nullable|in:oui,non',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'id_recurrent'=> 'nullable|exists:facture_recurrentes,id',
            'type_paiement' => 'required|in:immediat,echeance,facture_Accompt',
            'id_paiement' => 'nullable|required_if:type_paiement,immediat|exists:payements,id',
            'echeances' => 'nullable|required_if:type_paiement,echeance|array',
            'echeances.*.date_pay_echeance' => 'required|date',
            'echeances.*.montant_echeance' => 'required|numeric|min:0',
            'facture_accompts' => 'nullable|required_if:type_paiement,facture_Accompt|array',
            'facture_accompts.*.num_factureAccomp' => 'nullable|string',
            'facture_accompts.*.titreAccomp' => 'required|string',
            'facture_accompts.*.dateAccompt' => 'required|date',
            'facture_accompts.*.dateEcheance' => 'required|date',
            'facture_accompts.*.montant' => 'required|numeric|min:0',
            'facture_accompts.*.commentaire' => 'nullable|string',
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
    

        $statutPaiement = $request->type_paiement === 'immediat' ? 'payer' : 'en_attente';

        $datePaiement = $request->type_paiement === 'immediat' ? now() : null;

        $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $facture = Facture::create([
            'num_facture' => $request->input('num_facture') ?: $numFacture,
            'client_id' => $request->client_id,
            'type_facture'=>"simple",
            'date_creation' => now(),
            'date_paiement' => $datePaiement,
            'reduction_facture' => $request->input('reduction_facture', 0),
            'active_Stock' => $request->active_Stock ?? 'oui',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_fact' => $request->input('note_fact'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'type_paiement' => $request->input('type_paiement'),
            'statut_paiement' => $statutPaiement,
            'id_paiement' => $request->type_paiement == 'immediat' ? $request->id_paiement : null,
            'id_recurrent' => $request->input('id_recurrent'),
        ]);
    
        $facture->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'facture');

        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
            // $prixTotalArticleTva = $quantite * $prixUnitaire * (1 + $TVA / 100) * (1 - $reduction / 100);
            // $prixTotalArticle = $quantite * $prixUnitaire * (1 - $reduction / 100);

            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArtcleFacture::create([
                'id_facture' => $facture->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
    
        // Gestion des échéances si type_paiement est 'echeance'
        if ($request->type_paiement == 'echeance') {
            foreach ($request->echeances as $echeanceData) {
                Echeance::create([
                    'facture_id' => $facture->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }
    
        // Gestion des factures d'acompte si type_paiement est 'facture_Accompt'
        if ($request->type_paiement == 'facture_Accompt') {
            foreach ($request->facture_accompts as $accomptData) {
                FactureAccompt::create([
                    'facture_id' => $facture->id,
                    'num_factureAccompt' => $accomptData['num_factureAccompt'] ?? $numFacture,
                    'titreAccomp' => $accomptData['titreAccomp'],
                    'dateAccompt' => $accomptData['dateAccompt'],
                    'dateEcheance' => $accomptData['dateEcheance'],
                    'montant' => $accomptData['montant'],
                    'commentaire' => $accomptData['commentaire'] ?? '',
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
                Echeance::create([
                    'facture_id' => $facture->id,
                    'date_pay_echeance' => $accomptData['dateEcheance'],
                    'montant_echeance' => $accomptData['montant'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }

        //gestion paiements reçus        
        if ($request->type_paiement == 'immediat') {
                PaiementRecu::create([
                    'facture_id' => $facture->id,
                    'id_paiement' => $request->id_paiement,
                    'date_recu' => now(),
                    'montant' => $facture->prix_TTC,
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            
        }


        if ($facture->active_Stock == 'oui') {
            foreach ($facture->articles as $article) {
                if (Stock::where('article_id', $article->id_article)->exists()) {

                    // Récupérer le dernier stock pour cet article
                    $lastStock = Stock::where('article_id', $article->id_article)->orderBy('created_at', 'desc')->first();
        
                    $numStock = $lastStock->num_stock;
        
                    // Créer une nouvelle entrée de stock
                    $stock = new Stock();
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock; 
                    $stock->libelle = $lastStock->libelle;
                    $stock->disponible_avant = $lastStock->disponible_avant;
                    $stock->modif = $article->quantite_article;
                    $stock->disponible_apres = $lastStock->disponible_apres - $article->quantite_article;
                    $stock->article_id = $article->id_article;
                    $stock->facture_id = $facture->id;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateurId;
                    $stock->user_id = $userId;
                    $stock->save();
                }
            }
        }
    
        return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture], 201);

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
            'Montant_total' => $article->prix_total_article,
            'Montant_total_avec_TVA' => $article->prix_total_tva_article,
        ];
    }

    return response()->json(['articles' => $response]);
}
public function listerToutesFactures()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $factures = Facture::with('client','paiement')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Facture::with('client','paiement')
            ->where('archiver', 'non')
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
        'num_facture' => $facture->num_facture,
        'date_creation' => $facture->date_creation,
        'date_paiement' => $facture->date_paiement,
        'prix_HT' => $facture->prix_HT,
        'prix_TTC' => $facture->prix_TTC,
        'note_fact' => $facture->note_fact,
        'prenom_client' => $facture->client->prenom_client, 
        'nom_client' => $facture->client->nom_client, 
        'active_Stock' => $facture->active_Stock,
        'type_paiement' => $facture->type_paiement,
        'moyen_paiement' => $facture->paiment->nom_payement ?? null,
        'statut_paiement' => $facture->statut_paiement,
    ];
}

return response()->json(['factures' => $response]);
}

public function listerFacturesEcheance()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

    $factures = Facture::with('echeances','client')
         ->where('archiver', 'non')
        ->where('type_paiement', 'echeance')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Facture::with('echeances','client')
            ->where('archiver', 'non')
            ->where('type_paiement', 'echeance')
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

    return response()->json(['factures_echeance' => $factures], 200);
}

public function listerFacturesAccompt()
{ if (auth()->guard('apisousUtilisateur')->check()) {
    $sousUtilisateurId = auth('apisousUtilisateur')->id();
    $userId = auth('apisousUtilisateur')->user()->id_user; 

$factures = Facture::with('factureAccompts','client')
     ->where('archiver', 'non')
    ->where('type_paiement', 'facture_Accompt')
    ->where(function ($query) use ($sousUtilisateurId, $userId) {
        $query->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
    })
    ->get();
} elseif (auth()->check()) {
    $userId = auth()->id();

    $factures = Facture::with('factureAccompts','client')
        ->where('archiver', 'non')
        ->where('type_paiement', 'facture_Accompt')
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

return response()->json(['factures_accompt' => $factures], 200);
}

public function listerFacturesPayer()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

    $factures = Facture::with('client')
         ->where('archiver', 'non')
        ->where('type_paiement', 'immediat')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Facture::with('client')
            ->where('archiver', 'non')
            ->where('type_paiement', 'immediat')
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

    return response()->json(['factures_Payer' => $factures], 200);
}

public function listerFacturesSupprimer()
{
    $factures = Facture::with('client')
        ->where('archiver', 'oui')
        ->get();

    return response()->json(['factures_accompt' => $factures], 200);
}

public function supprimeArchiveFacture($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $facture = Facture::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($facture){
                $facture->archiver = 'oui';
                $facture->save();
            return response()->json(['message' => 'facture supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet facture'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $facture = facture::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($facture){
                    $facture->archiver = 'oui';
                    $facture->save();
                return response()->json(['message' => 'facture supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet facture'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function listeFactureParClient($clientId){

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

    $factures = Facture::where('archiver', 'non')
        ->where('client_id', $clientId)
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Facture::where('archiver', 'non')
            ->where('client_id', $clientId)
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

    return response()->json(['factures_Payer' => $factures], 200);
}

public function listerFacturesRecurrentes()
{

    return response()->json(['factures_Payer' => FactureRecurrente::all()], 200);
}

public function ArreteCreationAutomatiqueFactureRecurrente($id)
{
    $facture = Facture::find($id);
    $facture->id_recurrent = null;
    $facture->save();
    return response()->json(['message' => 'Creation automatique de facture recurrente arreter avec succes.'], 200); 
}

}