<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Devi;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\ArticleDevi;
use App\Models\BonCommande;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\ModelDocument;
use App\Models\FactureAccompt;
use App\Models\facture_Etiquette;
use App\Models\ArticleBonCommande;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DeviController extends Controller
{
    public function creerDevi(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_devi' => 'nullable|string',
            'reduction_devi' => 'nullable|numeric',
            'date_devi'=>'required|date',
            'date_limite'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'statut_devi'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',

            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'nullable|date',
            'echeances.*.montant_echeance' => 'nullable|numeric|min:0',

            'facture_accompts' => 'nullable|array',
            'facture_accompts.*.num_factureAccomp' => 'nullable|string',
            'facture_accompts.*.titreAccomp' => 'nullable|string',
            'facture_accompts.*.dateAccompt' => 'nullable|date',
            'facture_accompts.*.dateEcheance' => 'nullable|date',
            'facture_accompts.*.montant' => 'nullable|numeric|min:0',
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

        $typeDocument = 'devis';
        $numDevi= NumeroGeneratorService::genererNumero($userId, $typeDocument);
        // Création de la facture
        $devi = Devi::create([
            'num_devi' => $numDevi,
            'client_id' => $request->client_id,
            'date_devi' => $request->date_devi,
            'date_limite' => $request->date_limite,
            'reduction_devi' => $request->input('reduction_facture', 0),
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_devi' => $request->input('note_devi'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_devi' => $request->statut_devi ?? 'en_attente',
        ]);
        
            $devi->save();
            NumeroGeneratorService::incrementerCompteur($userId, 'devis');

            Artisan::call('optimize:clear');

            Historique::create([
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
                'message' => 'Des Devis ont été créés',
                'id_devi' => $devi->id
            ]);

            
        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'devi_id' => $devi->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }

        Devi::envoyerNotificationSDeviExpirer($devi);
        
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleDevi::create([
                'id_devi' => $devi->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
            if ($request->echeances !== null) {
                foreach ($request->echeances as $echeanceData) {
                    Echeance::create([
                        'devi_id' => $devi->id,
                        'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                        'montant_echeance' => $echeanceData['montant_echeance'],
                        'sousUtilisateur_id' => $sousUtilisateurId,
                        'user_id' => $userId,
                    ]);
                }
            }
        
            // Gestion des factures d'acompte si type_paiement est 'facture_Accompt'
            if ($request->facture_accompts !== null) {
                foreach ($request->facture_accompts as $accomptData) {
                    FactureAccompt::create([
                        'devi_id' => $devi->id,
                        'num_factureAccompt' => $accomptData['num_factureAccompt'],
                        'titreAccomp' => $accomptData['titreAccomp'],
                        'dateAccompt' => $accomptData['dateAccompt'],
                        'dateEcheance' => $accomptData['dateEcheance'],
                        'montant' => $accomptData['montant'],
                        'commentaire' => $accomptData['commentaire'] ?? '',
                        'sousUtilisateur_id' => $sousUtilisateurId,
                        'user_id' => $userId,
                    ]);
                    Echeance::create([
                        'devi_id' => $devi->id,
                        'date_pay_echeance' => $accomptData['dateEcheance'],
                        'montant_echeance' => $accomptData['montant'],
                        'sousUtilisateur_id' => $sousUtilisateurId,
                        'user_id' => $userId,
                    ]);
                }
            }
    
        return response()->json(['message' => 'Devi créée avec succès', 'Devi' => $devi], 201);

    }
public function TransformeDeviEnFacture($deviId)
{
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

    $devi = Devi::find($deviId);
    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

    $devi->statut_devi = 'transformer';
    $devi->save();
    Artisan::call('optimize:clear');


    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Devis ont été transformés en Facture',
        'id_devi' => $devi->id
    ]);

    return response()->json(['message' => 'Devi transformée avec succès', 'Devi' => $devi], 200);
}

public function TransformeDeviEnBonCommande($deviId)
{
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
    $devi = Devi::find($deviId);
    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

    $devi->statut_devi = 'transformer';
    $devi->save();
    Artisan::call('optimize:clear');
    
    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Devis ont été transformés en Bon de Commande',
        'id_devi' => $devi->id
    ]);
    return response()->json(['message' => 'Devi transformée avec succès', 'Devi' => $devi], 200);
}

public function annulerDevi($deviId)
{
    $devi = Devi::find($deviId);

    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

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

    // Mettre à jour le statut du devis en "annuler"
    $devi->statut_devi = 'annuler';
    $devi->save();
    Artisan::call('optimize:clear');


    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Devis ont été Annulés',
        'id_devi' => $devi->id
    ]);
    return response()->json(['message' => 'Devi annulé avec succès', 'devi' => $devi], 200);
}

public function supprimerDevi($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $devi = Devi::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($devi){
                $devi->archiver = 'oui';
                $devi->save();
                Artisan::call('optimize:clear');

            return response()->json(['message' => 'devi supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet devi'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $devi = Devi::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($devi){
                    $devi->archiver = 'oui';
                    $devi->save();
                    Artisan::call('optimize:clear');

                return response()->json(['message' => 'devi supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet devi'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function listerToutesDevi()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;

        // Utiliser le cache pour stocker les devis
        $devis = Cache::remember("devis", 3600, function () use ($sousUtilisateurId, $userId) {
            return Devi::with('client', 'articles.article', 'Etiquettes.etiquette')
                ->where('archiver', 'non')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        // Utiliser le cache pour stocker les devis
        $devis = Cache::remember("devis", 3600, function () use ($userId) {
            return Devi::with('client', 'articles.article', 'Etiquettes.etiquette')
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
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    $response = [];
    foreach ($devis as $devi) {
        $response[] = [
            'id' => $devi->id,
            'prix_Ht' => $devi->prix_HT,
            'prix_Ttc' => $devi->prix_TTC,
            'note_devi' => $devi->note_devi,
            'prenom_client' => $devi->client->prenom_client,
            'nom_client' => $devi->client->nom_client,
            'active_Stock' => $devi->active_Stock,
            'reduction_devi' => $devi->reduction_devi,
            'articles' => ($devi->articles ?? collect())->map(function ($articledevi) {
                return [
                    'id' => optional($articledevi->article)->id,
                    'nom' => optional($articledevi->article)->nom_article,
                    'quantite' => $articledevi->quantite_article,
                    'prix' => $articledevi->prix_total_tva_article,
                ];
            })->all(), // Utilisez ->all() pour retourner un tableau
            'etiquettes' => ($devi->Etiquettes ?? collect())->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all(), // Utilisez ->all() pour retourner un tableau
        ];
    }

    return response()->json(['devis' => $response]);
}



public function DetailsDevis($id)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = null;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
    $devi = Cache::remember("devi", 3600, function () use ($id) {
        
    return Devi::where('id', $id)
                ->with(['client', 'articles.article','Etiquettes.etiquette', 'echeances', 'factureAccompts'])
                ->first();

    });
    if (!$devi) {
        return response()->json(['error' => 'devi non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($devi->date_devi);

    $response = [
        'id_devi' => $devi->id,
        'numero_devi' => $devi->num_devi,
        'date_creation' => $dateCreation->format('Y-m-d H:i:s'),
        'date_limite' => $devi->date_limite,
        'client' => [
            'id_client' => $devi->client->id,
            'nom' => $devi->client->nom_client,
            'prenom' => $devi->client->prenom_client,
            'adresse' => $devi->client->adress_client,
            'telephone' => $devi->client->tel_client,
            'nom_entreprise'=> $devi->client->nom_entreprise,
        ],
        'note_devi' => $devi->note_devi,
        'prix_HT' => $devi->prix_HT,
        'prix_TTC' => $devi->prix_TTC,
        'reduction_devi' => $devi->reduction_devi,
        'statut_devi' => $devi->statut_devi,
        'nom_comptable' => $devi->compteComptable->nom_compte_comptable ?? null,
        'articles' => [],
        'echeances' => [],
        'nombre_echeance' => $devi->echeances ? $devi->echeances->count() : 0,
        'facture_accomptes' => [],

        'etiquettes' => $devi->Etiquettes->map(function ($etiquette) {
            return [
                'id' => optional($etiquette->etiquette)->id,
                'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
            ];
        })->filter(function ($etiquette) {
            return !is_null($etiquette['id']);
        })->values()->all(),
    
        
    ];

    if ($devi->articles && $devi->articles->isNotEmpty()) {
        foreach ($devi->articles as $articledevi) {
            $response['articles'][] = [
                'id_article' => $articledevi->id_article,
                'nom_article' => $articledevi->article->nom_article,
                'TVA' => $articledevi->TVA_article,
                'quantite_article' => $articledevi->quantite_article,
                'prix_unitaire_article' => $articledevi->prix_unitaire_article,
                'prix_total_tva_article' => $articledevi->prix_total_tva_article,
                'prix_total_article' => $articledevi->prix_total_article,
                'reduction_article' => $articledevi->reduction_article,
            ];
        }
    }

    if ($devi->echeances && $devi->echeances->isNotEmpty()) {
        foreach ($devi->echeances as $echeance) {
            $response['echeances'][] = [
                'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                'montant_echeance' => $echeance->montant_echeance,
            ];
        }
    }

    if ($devi->factureAccompts && $devi->factureAccompts->isNotEmpty()) {
        foreach ($devi->factureAccompts as $factureAccompt) {
            $response['facture_accomptes'][] = [
                'titreAccomp' => $factureAccompt->titreAccomp,
                'dateAccompt' => Carbon::parse($factureAccompt->dateAccompt)->format('Y-m-d'),
                'dateEcheance' => Carbon::parse($factureAccompt->dateEcheance)->format('Y-m-d'),
                'montant' => $factureAccompt->montant,
                'commentaire' => $factureAccompt->commentaire,
            ];
        }
    }

    return response()->json(['devi_details' => $response], 200);
}

public function listeDeviParClient($clientId)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $devis = Cache::remember("devis", 3600, function () use ($sousUtilisateurId, $userId, $clientId) {
        
        return Devi::with('articles.article','Etiquettes.etiquette')
            ->where('client_id', $clientId)
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
        });
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $devis = Cache::remember("devis", 3600, function () use ($userId, $clientId) {
            
        return Devi::with('articles.article','Etiquettes.etiquette')
            ->where('client_id', $clientId)
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
// Construire la réponse avec les détails des devis et les noms des clients
$response = [];
foreach ($devis as $devi) {
    $response[] = [
        'id' => $devi->id,
        'num_devi' => $devi->num_devi,
        'date_devi' => $devi->date_devi,
        'statut_devi' => $devi->statut_devi,
        'date_limite' => $devi->date_limite,
        'prix_Ht' => $devi->prix_HT,
        'prix_Ttc' => $devi->prix_TTC,
        'note_devi' => $devi->note_devi,
        'active_Stock' => $devi->active_Stock,
        'reduction_devi' => $devi->reduction_devi,
        'articles' => $devi->articles->map(function ($articleDevi) {
                return [
                    'id' => $articleDevi->article->id,
                    'nom' => $articleDevi->article->nom_article,
                    'quantite' => $articleDevi->quantite_article,
                    'prix' => $articleDevi->prix_total_tva_article,
                ];
            }),

        'etiquettes' => $devi->Etiquettes->map(function ($etiquette) {
            return [
                'id' => optional($etiquette->etiquette)->id,
                'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
            ];
        })->filter(function ($etiquette) {
            return !is_null($etiquette['id']);
        })->values()->all(),
    
    ];
}

return response()->json(['devis' => $response]);
}
public function exporterDevis()
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



    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $Devis = Devi::with(['client', 'articles.article'])
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $Devis = Devi::with(['client', 'articles.article'])
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
    foreach ($Devis as $devi) {

        if ($devi->articles->isNotEmpty()) {
            $nomarticles = $devi->articles->map(function ($articles) {
                return $articles->article ? $articles->article->nom_article : '';
            })->filter()->implode(', ');


            $nom_article = $nomarticles;
        

        $sheet->setCellValue('A' . $row, $devi->num_devi);
        $sheet->setCellValue('B' . $row, $devi->date_devi);
        $sheet->setCellValue('C' . $row, $nom_article);
        $sheet->setCellValue('D' . $row, $devi->client->num_client);
        $sheet->setCellValue('E' . $row, $devi->client->nom_client . ' - ' . $devi->client->prenom_client);
        $sheet->setCellValue('F' . $row, $devi->client->email_client);
        $sheet->setCellValue('G' . $row, $devi->prix_HT);
        $sheet->setCellValue('H' . $row, $devi->prix_TTC);
        $sheet->setCellValue('I' . $row, $devi->statut_devi);
        $sheet->setCellValue('J' . $row, $devi->note_devi);

        $row++;
    }
  }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Devis.xlsx';

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
  


public function genererPDFDevis($devisId, $modelDocumentId)
{
    // 1. Récupérer le devis et le modèle de document depuis la base de données
    $devis = Devi::with(['user', 'client', 'articles.article', 'echeances'])->find($devisId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$devis || !$modelDocument) {
        return response()->json(['error' => 'Devis ou modèle introuvable'], 404);
    }

    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('{{num_devi}}', $devis->num_devi, $content);
    $content = str_replace('{{expediteur_nom}}', $devis->user->name, $content);
    $content = str_replace('{{expediteur_email}}', $devis->user->email, $content);
    $content = str_replace('{{expediteur_tel}}', $devis->user->tel_entreprise ?? 'N/A', $content);
    
    $content = str_replace('{{destinataire_nom}}', $devis->client->prenom_client . ' ' . $devis->client->nom_client, $content);
    $content = str_replace('{{destinataire_email}}', $devis->client->email_client, $content);
    $content = str_replace('{{destinataire_tel}}', $devis->client->tel_client, $content);
    
    $content = str_replace('{{date_devis}}', \Carbon\Carbon::parse($devis->created_at)->format('d/m/Y'), $content);
    
    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($devis->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " fcfa</td>
            <td>" . number_format($article->prix_total_article, 2) . " fcfa</td>
        </tr>";
    }
    $content = str_replace('{{articles}}', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('{{montant_total}}', number_format($devis->prix_TTC, 2) . " fcfa", $content);

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
    return $dompdf->stream('devis_' . $devis->num_devi . '.pdf');
}

}
