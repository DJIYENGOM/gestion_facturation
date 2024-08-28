<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tva;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\FactureAvoir;
use App\Models\Notification;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\FactureRecurrente;
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
            

                $articleDb = Article::find($article->id_article);
        
                if ($articleDb && isset($articleDb->quantite) && isset($articleDb->quantite_alert)) {
                    // Créer une notification si la quantité atteint ou est inférieure à la quantité d'alerte
                    if ($articleDb->quantite <= $articleDb->quantite_alert) {
                        Notification::create([
                            'sousUtilisateur_id' => $sousUtilisateurId,
                            'user_id' => $userId,
                            'id_article' => $articleDb->id,
                            'message' => 'La quantité des produits (' . $articleDb->nom_article . ') atteint la quantité d\'alerte.',
                        ]);
                    }
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

public function RapportFacture(Request $request)
{
    // Valider les dates d'entrée
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
        'selection' => 'nullable|string|in:clients,produits,factures',// Nouveau paramètre pour déterminer la sélection
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    // Récupérer les dates
    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin');
    $selection = $request->input('selection') ?? 'factures';

    // Récupérer l'utilisateur connecté
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

    // Retourner le rapport et le nombre de factures
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
        return response()->json(['error' => 'Unauthorized'], 401);
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


}