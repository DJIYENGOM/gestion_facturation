<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\Tva;
use Dompdf\Options;
use App\Models\Solde;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Depense;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\FactureAvoir;
use App\Models\JournalVente;
use App\Models\Notification;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\CommandeAchat;
use App\Models\ModelDocument;
use App\Models\FactureAccompt;
use App\Models\CompteComptable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\facture_Etiquette;
use App\Models\FactureRecurrente;
use App\Models\MessageNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;



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
            'utiliser_solde' => 'nullable|boolean',
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
            'articles.*.prix_total_tva_article'=>'nullable|numeric',

            'etiquettes' => 'nullable|array',
            'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',
        ]);
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateurId = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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

        Artisan::call('optimize:clear');

        Tva::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'tva_recolte' => $facture->prix_TTC - $facture->prix_HT, 
            'tva_deductif'=> 0,
            'tva_reverse'=> 0
        ]);

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des Factures ont été créées',
            'id_facture' => $facture->id
        ]);

        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'facture_id' => $facture->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }
        

        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;

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
               $echeance = Echeance::create([
                    'facture_id' => $facture->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);

                Echeance::envoyerNotificationSEcheanceImpayer($echeance);

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

      // Gestion paiements reçus        
if ($request->type_paiement == 'immediat') {

    if ($request->utiliser_solde == 1) {
        $solde = Solde::where('client_id', $request->client_id)->first();
        
        if ($solde && $solde->montant > 0) { // Assurer que le solde existe et est supérieur à 0
            if ($solde->montant >= $request->prix_TTC) {
                // Si le solde couvre la totalité de la facture
                $solde->montant -= $request->prix_TTC;
                $solde->save();

                PaiementRecu::create([
                    'facture_id' => $facture->id,
                    'id_paiement' => $request->id_paiement,
                    'date_recu' => now(),
                    'montant' => $request->prix_TTC,
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            } else {
                // Si le solde est inférieur à la facture, payer avec le solde et le reste avec un autre moyen
                PaiementRecu::create([
                    'facture_id' => $facture->id,
                    'id_paiement' => $request->id_paiement,
                    'date_recu' => now(),
                    'montant' => $solde->montant,
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);

                PaiementRecu::create([
                    'facture_id' => $facture->id,
                    'id_paiement' => $request->id_paiement,
                    'date_recu' => now(),
                    'montant' => $request->prix_TTC - $solde->montant,
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);

                // Mettre le solde à zéro
                $solde->montant = 0;
                $solde->save();
            }
        } else {
            return response()->json(['error' => 'Solde insuffisant ou inexistant'], 400);
        }
    } else {
        // Si le solde n'est pas utilisé, procéder à un paiement normal
        PaiementRecu::create([
            'facture_id' => $facture->id,
            'id_paiement' => $request->id_paiement,
            'date_recu' => now(),
            'montant' => $facture->prix_TTC,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    }
}



        if ($facture->active_Stock == 'oui') {
            foreach ($facture->articles as $article) {
                if (Stock::where('article_id', $article->id_article)->exists()) {

                    // Récupérer le dernier stock pour cet article
                    $lastStock = Stock::where('article_id', $article->id_article)->orderBy('created_at', 'desc')->first();
        
                    $numStock = $lastStock->num_stock;
        
                    // Créer une nouvelle entrée de stock
                    $stock = new Stock();
                    $stock->type_stock = 'Sortie';
                    $stock->date_stock = now()->format('Y-m-d');
                    $stock->num_stock = $numStock; 
                    $stock->libelle = $lastStock->libelle;
                    $stock->disponible_avant = $lastStock->disponible_apres;
                    $stock->modif = $article->quantite_article;
                    $stock->disponible_apres = $lastStock->disponible_apres - $article->quantite_article;
                    $stock->article_id = $article->id_article;
                    $stock->facture_id = $facture->id;
                    $stock->bonCommande_id = null;
                    $stock->livraison_id = null;
                    $stock->statut_stock = 'Vente N°' . $facture->num_facture;
                    $stock->sousUtilisateur_id = $sousUtilisateurId;
                    $stock->user_id = $userId;
                    $stock->save();
                    Artisan::call(command: 'optimize:clear');

                    $article = Article::find($article->id_article);
                    $article->quantite_disponible = $stock->disponible_apres;
                    $article->save();
                    Artisan::call(command: 'optimize:clear');

                }
            

                $article = Article::find($article->id_article);

                if ($article) {
                    $notificationConfig = Notification::where(function($query) use ($article, $sousUtilisateurId) {
                        $query->where('user_id', $article->user_id)
                              ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                    })->first();
                
                    if ($notificationConfig) {
                        if ($notificationConfig->produit_rupture && $notificationConfig->quantite_produit > 0) {
                            if ($article->quantite_disponible <= $notificationConfig->quantite_produit) {
                                MessageNotification::create([
                                    'sousUtilisateur_id' => $sousUtilisateurId,
                                    'user_id' => $userId,
                                    'article_id' => $article->id,
                                    'message' => 'Produits ont moins de ' . $notificationConfig->quantite_produit . ' en stock',
                                ]);
                            }
                        }
                    }
                } 
                

        }

        }

$compteVentesMarchandises = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')->first();
$compteVentesServices = CompteComptable::where('nom_compte_comptable', 'Prestations de services')->first();
$compteClientsDivers = CompteComptable::where('nom_compte_comptable', 'Clients divers')->first();
$compteTVA = CompteComptable::where('nom_compte_comptable', 'TVA collectée')->first();
$compteAcomptes = CompteComptable::where('nom_compte_comptable', 'Acomptes recus')->first();
$compteBanque = CompteComptable::where('nom_compte_comptable', 'Banque')->first();

// Créer l'entrée pour "Clients divers"
if ($compteClientsDivers) {
    JournalVente::create([
        'id_facture' => $facture->id,
        'id_compte_comptable' => $compteClientsDivers->id,
        'debit' => $request->prix_TTC,
        'credit' => 0,
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);
}

foreach ($request->articles as $article) {

    $articleDetails = Article::find($article['id_article']); // Récupère les détails de l'article

    if ($articleDetails) {
        $typeArticle = $articleDetails->type_article;     

    $tva = $article['TVA_article'] ?? 0;
    $quantite = $article['quantite_article'] ?? 0;
    $prixUnitaire = $article['prix_unitaire_article'] ?? 0;
    $montantTVA =  $quantite * $prixUnitaire * (1 + $tva / 100) - $quantite * $prixUnitaire ?? 0;
    $prixHT = $article['prix_total_article'] ?? 0;
  
    // Si TVA présente, créer une entrée pour "TVA collectée"
    if ($tva > 0 && $compteTVA) {
        JournalVente::create([
            'id_facture' => $facture->id,
            'id_article' => $article['id_article'],
            'id_compte_comptable' => $compteTVA->id,
            'debit' => 0,
            'credit' => $montantTVA, // TVA collectée
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    }

    // Créer une entrée pour le compte de ventes correspondant
    if ($typeArticle == 'produit' && $compteVentesMarchandises) {
        JournalVente::create([
            'id_facture' => $facture->id,
            'id_article' => $article['id_article'],
            'id_compte_comptable' => $compteVentesMarchandises->id,
            'debit' => 0,
            'credit' => $prixHT, // Utilise le montant HT ici
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    } elseif ($typeArticle == 'service' && $compteVentesServices) {
        JournalVente::create([
            'id_facture' => $facture->id,
            'id_article' => $article['id_article'],
            'id_compte_comptable' => $compteVentesServices->id,
            'debit' => 0,
            'credit' => $prixHT, // Utilise le montant HT ici
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    
    }
   
    }
    } 
    if($facture->type_paiement == 'facture_Accompt'){ 
        if ($compteAcomptes) {
        JournalVente::create([
            'id_facture' => $facture->id,
            'id_article' => $article['id_article'],
            'id_compte_comptable' => $compteAcomptes->id,
            'debit' => 0,
            'credit' => $prixHT, // Utilise le montant HT ici
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    }
       if ($compteBanque) {
         JournalVente::create([
            'id_facture' => $facture->id,
            'id_article' => $article['id_article'],
            'id_compte_comptable' => $compteBanque->id,
            'debit' => $prixHT, // Utilise le montant HT ici
            'credit' => 0,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    }
}

    return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture], 201);
}


public function listeArticlesFacture($id_facture)
{

    $facture = Facture::findOrFail($id_facture);

    
    if ($facture->sousUtilisateur_id) {
        $userId = auth('apisousUtilisateur')->id();
        if ($facture->sousUtilisateur_id != $userId) {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    } elseif ($facture->user_id) {
        $userId = auth()->id();
        if ($facture->user_id != $userId) {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    }

    $articles = Cache::remember("articles_facture_" . $id_facture, 3600, function () use ($id_facture) {
        
        return ArtcleFacture::where('id_facture', $id_facture)->with('article')->get();
    });
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

        $factures = Cache::remember("factures", 3600, function () use ($sousUtilisateurId, $userId) {
            
        return Facture::with('client','paiement')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();

        });

    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Cache::remember("factures", 3600, function () use ($userId) {
            
       
        return Facture::with('client','paiement')
            ->where('archiver', 'non')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

    $factures = Cache::remember("factures_echeance", 3600, function () use ($sousUtilisateurId, $userId) {
        
        return Facture::with('echeances','client')
         ->where('archiver', 'non')
        ->where('type_paiement', 'echeance')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->get();
    });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Cache::remember("factures_echeance", 3600, function () use ($userId) {
        return Facture::with('echeances','client')
            ->where('archiver', 'non')
            ->where('type_paiement', 'echeance')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    return response()->json(['factures_echeance' => $factures], 200);
}

public function listerFacturesAccompt()
{ if (auth()->guard('apisousUtilisateur')->check()) {
    $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
    $sousUtilisateurId = auth('apisousUtilisateur')->id();
    $userId = auth('apisousUtilisateur')->user()->id_user; 

$factures = Cache::remember("factures_accompt", 3600, function () use ($sousUtilisateurId, $userId) {
    
    return Facture::with('factureAccompts','client')
     ->where('archiver', 'non')
    ->where('type_paiement', 'facture_Accompt')
    ->where(function ($query) use ($sousUtilisateurId, $userId) {
        $query->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
    })
    ->get();
});
} elseif (auth()->check()) {
    $userId = auth()->id();

    $factures = Cache::remember("factures_accompt", 3600, function () use ($userId) {
        
    return Facture::with('factureAccompts','client')
        ->where('archiver', 'non')
        ->where('type_paiement', 'facture_Accompt')
        ->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                    $query->where('id_user', $userId);
                });
        })
        ->get();
    });
} else {
    return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
}

return response()->json(['factures_accompt' => $factures], 200);
}

public function listerFacturesPayer()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

    $factures = Cache::remember("factures_payer", 3600, function () use ($sousUtilisateurId, $userId) {
        
        return Facture::with('client')
         ->where('archiver', 'non')
        ->where('statut_paiement', 'payer')
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId);
        })
        ->get();
    });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Cache::remember("factures_payer", 3600, function () use ($userId) {
            
         Facture::with('client')
            ->where('archiver', 'non')
            ->where('statut_paiement', 'payer')
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                        $query->where('id_user', $userId);
                    });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
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
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $facture = Facture::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($facture){
                $facture->archiver = 'oui';
                $facture->save();
                Artisan::call('optimize:clear');

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
                    Artisan::call('optimize:clear');
                return response()->json(['message' => 'facture supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet facture'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}


public function listeFactureParClient($clientId)
{
    $facturesSimples = [];
    $facturesAvoirs = [];

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = $sousUtilisateur->id_user;

        $facturesSimples = Cache::remember("factures", 3600, function () use ($sousUtilisateurId, $userId, $clientId) {
            
        return Facture::with('articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where('client_id', $clientId)
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->get();
        });

        $facturesAvoirs = Cache::remember("facturesAvoirs", 3600, function () use ($sousUtilisateurId, $userId, $clientId) {
            
            return FactureAvoir::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $facturesSimples = Cache::remember("factures", 3600, function () use ($userId, $clientId) {
            
       return Facture::with('articles.article', 'Etiquettes.etiquette')
            ->where('archiver', 'non')
            ->where('client_id', $clientId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                          $query->where('id_user', $userId);
                      });
            })
            ->get();
        });
        $facturesAvoirs = Cache::remenber("facturesAvoirs", 3600, function () use ($userId, $clientId) {
       
        return FactureAvoir::with('articles.article', 'Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                          $query->where('id_user', $userId);
                      });
            })
            ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    // Construire la réponse avec les détails combinés des factures simples et des factures d'avoirs
    $response = [];

    foreach ($facturesSimples as $facture) {
        $response[] = [
            'id' => $facture->id,
            'numero' => $facture->num_facture,
            'date_creation' => $facture->date_creation,
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'date' => $facture->date_creation,
            'statut_paiement' => $facture->statut_paiement,
            'type_facture' => $facture->type_facture,
            'note_fact' => $facture->note_fact,
            'reduction_facture' => $facture->reduction_facture,
            'type' => 'simple',
            'articles' => $facture->articles->map(function ($articleFacture) {
                return [
                    'id' => $articleFacture->article->id,
                    'nom' => $articleFacture->article->nom_article,
                    'quantite' => $articleFacture->quantite_article,
                    'prix' => $articleFacture->prix_total_tva_article,
                ];
            }),
            'etiquettes' => $facture->Etiquettes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
        ];
    }

    foreach ($facturesAvoirs as $facture) {
        $response[] = [
            'id' => $facture->id,
            'numero' => $facture->num_facture,
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'date' => $facture->date,
            'statut_paiement' => 'Note de crédit',
            'titre' => $facture->titre,
            'description' => $facture->description,
            'commentaire' => $facture->commentaire,
            'type_facture' => $facture->type_facture,
            'type' => 'avoir',
            'articles' => $facture->articles->map(function ($articleFactureAvoir) {
                return [
                    'id' => $articleFactureAvoir->article->id,
                    'nom' => $articleFactureAvoir->article->nom_article,
                    'quantite' => $articleFactureAvoir->quantite_article,
                    'prix' => $articleFactureAvoir->prprix_total_tva_articleix,
                ];
            }),
            'etiquettes' => $facture->Etiquettes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
        ];
    }

    // Trier la collection fusionnée par date de création
    usort($response, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    return response()->json(['factures' => $response]);
}


public function listerFacturesRecurrentes()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = auth('apisousUtilisateur')->user()->id_user;

        $factures = Cache::remember("factures_recurrentes", 3600, function () use ($sousUtilisateur_id, $user_id) {
            
        return FactureRecurrente::where(function ($query) use ($sousUtilisateur_id, $user_id) {
            $query->where('sousUtilisateur_id', $sousUtilisateur_id)
                ->orWhere('user_id', $user_id);
        })
        ->get();
        });
    } elseif (auth()->check()) {
        $user_id = auth()->id();

        $factures = Cache::remember("factures_recurrentes", 3600, function () use ($user_id) {
            
        return FactureRecurrente::with('client')
        ->where(function ($query) use ($user_id) {
            $query->where('user_id', $user_id)
                ->orWhereHas('sousUtilisateur', function ($query) use ($user_id) {
                    $query->where('id_user', $user_id);
                });
            })
                ->get();
        });
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    return response()->json(['factures_Recurrentes' => $factures], 200);
}

public function ArreteCreationAutomatiqueFactureRecurrente($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
    $facture = Facture::where('id', $id)
    ->where(function ($query) use ($userId, $sousUtilisateurId) {
        $query->where('user_id', $userId)
              ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
    })
    ->first();
    $facture->id_recurrent = null;
    $facture->save();
    Artisan::call('optimize:clear');

    return response()->json(['message' => 'Creation automatique de facture recurrente arreter avec succes.'], 200); 
}

public function RapportFacture(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
        'selection' => 'nullable|string|in:clients,produits,factures',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin') . ' 23:59:59'; //Inclure la fin de la journée
    $selection = $request->input('selection') ?? 'factures';

    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;

    $factures = Facture::with(['client', 'articles.article'])
        ->where('archiver', 'non')
        ->whereBetween('date_creation', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                    $query->where('id_user', $parentUserId);
                });
        })
        ->get();

    // Compter le nombre de factures
    $nombreFactures = $factures->count();

    // Générer le rapport en fonction de la sélection
    if ($selection === 'clients') {
        $rapport = $factures->groupBy('client_id')->map(function ($facturesParClient) {
            $totalHT = $facturesParClient->sum('prix_HT');
            $totalTTC = $facturesParClient->sum('prix_TTC');
            $client = $facturesParClient->first()->client;

            return [
                'client_nom' => $client->nom_client,
                'client_prenom' => $client->prenom_client,
                'total_HT' => $totalHT,
                'total_TTC' => $totalTTC,
            ];
        })->values();
    } elseif ($selection === 'produits') {
        $rapport = $factures->flatMap(function ($facture) {
            return $facture->articles;
        })->groupBy('id_article')->map(function ($articlesParProduit) {
            $totalHT = $articlesParProduit->sum('prix_total_article');
            $totalTTC = $articlesParProduit->sum('prix_total_tva_article');
            $produit = $articlesParProduit->first()->article;

            return [
                'produit_nom' => $produit->nom_article,
                'total_HT' => $totalHT,
                'total_TTC' => $totalTTC,
            ];
        })->values();
        
    }else{
        $rapport = $factures->map(function ($facture) {
            return [
                'num_facture' => $facture->num_facture,
                'date_facture' => $facture->date_creation,
                'prix_HT' => $facture->prix_HT,
                'prix_TTC' => $facture->prix_TTC,
                'client_nom' => $facture->client->nom_client,
                'client_prenom' => $facture->client->prenom_client,
                'statut_paiement' => $facture->statut_paiement,
                'articles' => $facture->articles->map(function ($articleFacture) {
                    return [
                        'nom_article' => $articleFacture->article->nom_article,
                        'quantite' => $articleFacture->quantite_article,
                        'prix_unitaire' => $articleFacture->prix_unitaire_article,
                        'prix_total_ht' => $articleFacture->prix_total_article,
                        'prix_total_ttc' => $articleFacture->prix_total_tva_article,
                    ];
                }),
            ];
        });
    }

    return response()->json([
        'nombre_factures' => $nombreFactures,
        'rapport' => $rapport,
    ]);
}


public function exportFactures()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'Date vente');
    $sheet->setCellValue('C1', 'Prod / Serv');
    $sheet->setCellValue('D1', 'Numero - Client');
    $sheet->setCellValue('E1', 'Client');
    $sheet->setCellValue('F1', 'Adresse électronique');
    $sheet->setCellValue('G1', 'Total HT');
    $sheet->setCellValue('H1', 'Total TTC');
    $sheet->setCellValue('I1', 'Statut');
    $sheet->setCellValue('J1', 'Note Interne');


    // Récupérer les factures simples et les factures d'avoir
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;

        $factures = Facture::with(['client', 'articles.article'])
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->get();

        $facturesAvoirs = FactureAvoir::with(['client', 'articles.article'])
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
            ->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

        $factures = Facture::with(['client', 'articles.article'])
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                          $query->where('id_user', $userId);
                      });
            })
            ->get();

        $facturesAvoirs = FactureAvoir::with(['client', 'articles.article'])
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

    // Fusionner les factures simples et les factures d'avoir
    $allFactures = $factures->merge($facturesAvoirs);

    // Remplir les données
    $row = 2;
    foreach ($allFactures as $facture) {
        if ($facture->articles->isNotEmpty()) {
            $nomarticles = $facture->articles->map(function ($articles) {
                return $articles->article ? $articles->article->nom_article : '';
            })->filter()->implode(', ');

       

            $email_client = $facture->client->email_client;
            $nom_article = $nomarticles;
          

            $sheet->setCellValue('A' . $row, $facture->num_facture);

            if ($facture instanceof FactureAvoir) {
                $sheet->setCellValue('B' . $row, $facture->date);
                $sheet->setCellValue('I' . $row, 'Note de crédit');
                $sheet->setCellValue('J' . $row, $facture->commentaire);
            } else {
                $sheet->setCellValue('B' . $row, $facture->date_creation);
                $sheet->setCellValue('I' . $row, $facture->statut_paiement);
                $sheet->setCellValue('J' . $row, $facture->note_fact);
            }

            $sheet->setCellValue('C' . $row, $nom_article);
            $sheet->setCellValue('D' . $row, $facture->client->num_client);
            $sheet->setCellValue('E' . $row, $facture->client->nom_client.'-'.$facture->client->prenom_client);
            $sheet->setCellValue('F' . $row, $email_client);
            $sheet->setCellValue('G' . $row, $facture->prix_HT);
            $sheet->setCellValue('H' . $row, $facture->prix_TTC);


            $row++;
        }
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Factures.xlsx';

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

public function genererPDFFacture($factureId, $modelDocumentId)
{
    // 1. Récupérer la facture et le modèle de document depuis la base de données
    $facture = Facture::with(['user', 'client', 'articles.article', 'echeances'])->find($factureId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$facture || !$modelDocument) {
        return response()->json(['error' => 'Facture ou modèle introuvable'], 404);
    }

    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('{{num_facture}}', $facture->num_facture, $content);
    $content = str_replace('{{expediteur_nom}}', $facture->user->name, $content);
    $content = str_replace('{{expediteur_email}}', $facture->user->email, $content);
    $content = str_replace('{{expediteur_tel}}', $facture->user->tel_entreprise ?? 'N/A', $content);

    $content = str_replace('{{destinataire_nom}}', $facture->client->prenom_client . ' ' . $facture->client->nom_client, $content);
    $content = str_replace('{{destinataire_email}}', $facture->client->email_client, $content);
    $content = str_replace('{{destinataire_tel}}', $facture->client->tel_client, $content);

    $content = str_replace('{{date_facture}}', \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y'), $content);

    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($facture->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " fcfa</td>
            <td>" . number_format($article->prix_total_article, 2) . " fcfa</td>
        </tr>";
    }
    $content = str_replace('{{articles}}', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('{{montant_total}}', number_format($facture->prix_TTC, 2) . " fcfa", $content);

    // Gérer les échéances
    if ($facture->type_paiement == 'echeance') {
        $echeancesHtml = '';
        foreach ($facture->echeances as $echeance) {
            $echeancesHtml .= "<tr>
                <td>" . \Carbon\Carbon::parse($echeance->date_pay_echeance)->format('d/m/Y') . "</td>
                <td>" . number_format($echeance->montant_echeance, 2) . " fcfa</td>
            </tr>";
        }
        $content = str_replace('{{echeances}}', $echeancesHtml, $content);
    } else {
        $content = str_replace('{{echeances}}', '', $content);
    }

    // Gérer les conditions de paiement
    if ($modelDocument->conditionsPaiementModel) {
        $conditionsPaiementHtml = "<h3>Conditions de paiement</h3><p>{$modelDocument->conditionPaiement}</p>";
        $content = str_replace('{{conditions_paiement}}', $conditionsPaiementHtml, $content);
    } else {
        $content = str_replace('{{conditions_paiement}}', '', $content);
    }

    // Gérer les coordonnées bancaires
    if ($modelDocument->coordonneesBancairesModel) {
        $coordonneesBancairesHtml = "<h3>Coordonnées bancaires</h3>
            <p>Titulaire du compte : {$modelDocument->titulaireCompte}</p>
            <p>IBAN : {$modelDocument->IBAN}</p>
            <p>BIC : {$modelDocument->BIC}</p>";
        $content = str_replace('{{coordonnees_bancaires}}', $coordonneesBancairesHtml, $content);
    } else {
        $content = str_replace('{{coordonnees_bancaires}}', '', $content);
    }

    // Gérer la note de pied de page
    if ($modelDocument->notePiedPageModel) {
        $content = str_replace('{{note_pied_page}}', "<p>{$modelDocument->peidPage}</p>", $content);
    } else {
        $content = str_replace('{{note_pied_page}}', '', $content);
    }

    // Gérer les signatures
    if ($modelDocument->signatureExpediteurModel) {
        $signatureExpediteurHtml = "<img src='/path/to/images/{$modelDocument->image_expediteur}' alt='Signature Expéditeur' />";
        $content = str_replace('{{signature_expediteur}}', $signatureExpediteurHtml, $content);
    } else {
        $content = str_replace('{{signature_expediteur}}', '', $content);
    }

    if ($modelDocument->signatureDestinataireModel) {
        $signatureDestinataireHtml = "<img src='/path/to/images/{$modelDocument->image_destinataire}' alt='Signature Destinataire' />";
        $content = str_replace('{{signature_destinataire}}', $signatureDestinataireHtml, $content);
    } else {
        $content = str_replace('{{signature_destinataire}}', '', $content);
    }

    // 3. Appliquer le CSS du modèle
    $css = $modelDocument->css;
    $content = str_replace('{{css}}', $css, $content);

    // 4. Configurer DOMPDF et générer le PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);  // Si vous avez des images distantes
    $dompdf = new Dompdf($options);
    
    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 5. Télécharger le PDF
    return $dompdf->stream('facture_' . $facture->num_facture . '.pdf');
}

public function RapportFluxTrésorerie(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin') . ' 23:59:59';
    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;

    $factures = Facture::with(['client', 'articles.article'])
        ->where('statut_paiement', 'payer')
        ->where('archiver', 'non')
        ->whereBetween('date_creation', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                    $query->where('id_user', $parentUserId);
                });
        })
        ->get();

    $depenses = Depense::with(['categorieDepense', 'fournisseur'])
        ->where('statut_depense', 'payer')
        ->whereBetween('created_at', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                    $query->where('id_user', $parentUserId);
                });
        })
        ->get();

        return compact('factures', 'depenses');
}

public function RapportResultat(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin') . ' 23:59:59';
    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;

    $factures = Facture::with(['articles.article' => function ($query) {
            $query->select('articles.id', 'articles.prix_achat');  
        }])
        ->select('id', 'prix_HT', 'prix_TTC', 'date_creation', 'statut_paiement')
        ->where('archiver', 'non')
        ->whereBetween('date_creation', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                    $query->where('id_user', $parentUserId);
                });
        })
        ->get();

    // Récupérer les dépenses avec leurs montants HT et TTC
    $depenses = Depense::select('id', 'montant_depense_ht', 'montant_depense_ttc', 'statut_depense', 'created_at')
        ->whereBetween('created_at', [$dateDebut, $dateFin])
        ->where(function ($query) use ($userId, $parentUserId) {
            $query->where('user_id', $userId)
                ->orWhere('user_id', $parentUserId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                    $query->where('id_user', $parentUserId);
                });
        })
        ->get();

    $result = [
        'factures' => $factures->map(function ($facture) {
            return [
                'id' => $facture->id,
                'montant_ht' => $facture->montant_ht,
                'montant_ttc' => $facture->montant_ttc,
                'date_creation' => $facture->date_creation,
                'statut_paiement' => $facture->statut_paiement,
                'articles' => $facture->articles->map(function ($articleFacture) {
                    return [
                        'article_id' => $articleFacture->article->id,
                        'prix_achat' => $articleFacture->article->prix_achat,
                    ];
                })
            ];
        }),
        'depenses' => $depenses->map(function ($depense) {
            return [
                'id' => $depense->id,
                'montant_ht' => $depense->montant_depense_ht,
                'montant_ttc' => $depense->montant_depense_ttc,
                'date_creation' => $depense->created_at,
                'statut_paiement' => $depense->statut_depense
            ];
        })
    ];

    return response()->json($result);
}

}