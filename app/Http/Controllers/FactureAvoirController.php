<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Article;
use App\Models\Facture;
use App\Models\Historique;
use App\Models\FactureAvoir;
use App\Models\JournalVente;
use Illuminate\Http\Request;
use App\Models\CompteComptable;
use App\Models\facture_Etiquette;
use App\Models\FactureRecurrente;
use App\Models\ArticleFactureAvoir;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;



class FactureAvoirController extends Controller
{

    public function creerFactureAvoir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'num_facture' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'facture_id' => 'nullable|exists:factures,id',
            'titre' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'commentaire' => 'nullable|string',
            'date'=>'required|date',
            'prix_HT'=> 'nullable|numeric',
            'prix_TTC'=>'nullable|numeric',
            'active_Stock' => 'nullable|in:oui,non',
            'doc_externe' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

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

        $typeDocument = 'facture';
        $numFactureAvoir= NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $path = null;
        if ($request->hasFile('doc_externe')) {
            $file = $request->file('doc_externe');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('facture_avoirs', $filename, 'public');
        }

        $factureAvoir = FactureAvoir::create([
            'num_facture' => $request->num_facture ?? $numFactureAvoir,
            'facture_id' => $request->facture_id ?? null,
            'type_facture'=>"avoir",
            'client_id' => $request->client_id,
            'titre' => $request->input('titre'),
            'description' => $request->input('description'),
            'date' => $request->date,
            'active_Stock' => $request->active_Stock ?? 'non',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'commentaire' => $request->input('commentaire'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'doc_externe' => $path,
        ]);
    
        $factureAvoir->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'facture');
        Artisan::call('optimize:clear');

    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleFactureAvoir::create([
                'id_factureAvoir' => $factureAvoir->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des Factures d\'avoir ont été crées',
            'id_facture_avoir' => $factureAvoir->id
        ]);

        
        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'factureAvoir_id' => $factureAvoir->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }
        $compteVentesMarchandises = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')->first();
        $compteVentesServices = CompteComptable::where('nom_compte_comptable', 'Prestations de services')->first();
        $compteClientsDivers = CompteComptable::where('nom_compte_comptable', 'Clients divers')->first();
        $compteTVA = CompteComptable::where('nom_compte_comptable', 'TVA collectée')->first();

        if ($compteClientsDivers) {
            JournalVente::create([
                'id_factureAvoir' => $factureAvoir->id,
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
          
            if ($tva > 0 && $compteTVA) {
                JournalVente::create([
                    'id_factureAvoir' => $factureAvoir->id,
                    'id_article' => $article['id_article'],
                    'id_compte_comptable' => $compteTVA->id,
                    'debit' => 0,
                    'credit' => $montantTVA, // TVA collectée
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        
            if ($typeArticle == 'produit' && $compteVentesMarchandises) {
                JournalVente::create([
                    'id_factureAvoir' => $factureAvoir->id,
                    'id_article' => $article['id_article'],
                    'id_compte_comptable' => $compteVentesMarchandises->id,
                    'debit' => 0,
                    'credit' => $prixHT, // Utilise le montant HT ici
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            } elseif ($typeArticle == 'service' && $compteVentesServices) {
                JournalVente::create([
                    'id_factureAvoir' => $factureAvoir->id,
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
        return response()->json(['message' => 'factureAvoir créée avec succès', 'factureAvoir' => $factureAvoir], 201);

    }

    public function listerToutesFacturesAvoirs()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $factures = Cache::remenber("facturesAvoirs", 3600, function () use ($sousUtilisateurId, $userId) {
                
             return FactureAvoir::with('client')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
            });
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $factures = Cache::remenber("facturesAvoirs", 3600, function () use ($userId) {
                
        
             FactureAvoir::with('client')
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
            'facture_id' => $facture->facture_id,
            'date' => $facture->date,
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'titre' => $facture->titre,
            'description' => $facture->description,
            'note_fact' => $facture->note_fact,
            'client_id' => $facture->client_id,
            'prenom_client' => $facture->client->prenom_client, 
            'nom_client' => $facture->client->nom_client, 
            'active_Stock' => $facture->active_Stock,
            'doc_externe' => $facture->doc_externe,
            'commentaire' => $facture->commentaire,
        ];
    }
    
    return response()->json(['factures' => $response]);
    }

    public function listerToutesFacturesSimpleAvoir()
    {
        $facturesSimples = [];
        $facturesAvoirs = [];
        
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; 
    
            $facturesSimples = Cache::remember("facturesSimples", 3600, function () use ($sousUtilisateurId, $userId) {
                
            return Facture::with('client', 'articles.article', 'Etiquettes.etiquette')
                ->where('archiver', 'non')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
            });
    
            $facturesAvoirs = Cache::remember("facturesAvoirs", 3600, function () use ($sousUtilisateurId, $userId) {
           
           return FactureAvoir::with('client', 'articles.article', 'Etiquettes.etiquette')
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                        ->orWhere('user_id', $userId);
                })
                ->get();
            });
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $facturesSimples = Cache::remember("facturesSimples", 3600, function () use ($userId) {
                
            return Facture::with('client', 'articles.article', 'Etiquettes.etiquette')
                ->where('archiver', 'non')
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                            $query->where('id_user', $userId);
                        });
                })
                ->get();
            });
            $facturesAvoirs = Cache::remember("facturesAvoirs", 3600, function () use ($userId) {
            
            return FactureAvoir::with('client', 'articles.article', 'Etiquettes.etiquette')
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
    
        // Construire la réponse avec les détails combinés des factures simples et des factures d'avoirs
        $response = [];
    
        foreach ($facturesSimples as $facture) {
            $response[] = [
                'id' => $facture->id,
                'numero' => $facture->num_facture,
                'date_creation' => $facture->date_creation,
                'prenom_client' => $facture->client->prenom_client,
                'nom_client' => $facture->client->nom_client,
                'prix_HT' => $facture->prix_HT,
                'prix_TTC' => $facture->prix_TTC,
                'date' => $facture->date_creation,
                'statut_paiement' => $facture->statut_paiement,
                'type_facture' => $facture->type_facture,
                'note_fact' => $facture->note_fact,
                'reduction_facture' => $facture->reduction_facture,
                'type'=> 'simple',
                'articles' => $facture->articles->map(function ($articleFacture) {
                return [
                    'id' => $articleFacture->article->id,
                    'nom' => $articleFacture->article->nom_article,
                    'quantite' => $articleFacture->quantite_article,
                    'prix' => $articleFacture->prix_total_tva_article,
                ];
            }),
            'etiquettes' => ($facture->Etiquettes ?? collect())->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
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
                'prenom_client' => $facture->client->prenom_client,
                'nom_client' => $facture->client->nom_client,
                'prix_HT' => $facture->prix_HT,
                'prix_TTC' => $facture->prix_TTC,
                'date' => $facture->date,
                'statut_paiement' => 'Note de crédit',
                'titre'=> $facture->titre,
                'description'=> $facture->description,
                'commentaire' => $facture->commentaire,
                'type_facture' => $facture->type_facture,
                'type'=> 'avoir',
                'articles' => $facture->articles->map(function ($articleFacture) {
                return [
                    'id' => $articleFacture->article->id,
                    'nom' => $articleFacture->article->nom_article,
                    'quantite' => $articleFacture->quantite_article,
                    'prix' => $articleFacture->prix_total_tva_article,
                ];
            }),
            'etiquettes' => ($facture->Etiquettes ?? collect())->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
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
    

    public function supprimerFacture($num_facture)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
    
            $facture = Facture::where('num_facture', $num_facture)
                ->where(function($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->first();
    
            if ($facture) {
                    $facture->delete();
                    Artisan::call('optimize:clear');

                    return response()->json(['message' => 'Facture simple supprimée avec succès.']);
                } 
                
            $factureAvoir = FactureAvoir::where('num_facture', $num_facture)
                ->where(function($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->first();
                if ($factureAvoir) {
                        $factureAvoir->delete();
                        Artisan::call('optimize:clear');
                        return response()->json(['message' => 'Facture d\'avoir supprimée avec succès.']);
                    }
                
            }
         elseif (auth()->check()) {
            $userId = auth()->id();
    
            $facture = Facture::where('num_facture', $num_facture)
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
    
            if ($facture) {
                    $facture->delete();
                    Artisan::call('optimize:clear');
                    return response()->json(['message' => 'Facture simple supprimée avec succès.']);
                } 

            $factureAvoir = FactureAvoir::where('num_facture', $num_facture)
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();

                if ($factureAvoir) {
                        $factureAvoir->delete();
                        Artisan::call('optimize:clear');
                        return response()->json(['message' => 'Facture d\'avoir supprimée avec succès.']);
                    }
                
            }
         else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        return response()->json(['error' => 'Facture non trouvée.'], 404);
    }
    

    public function DetailsFacture($num_facture)
    {
        // Vérifier l'authentification pour les sous-utilisateurs
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
          if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;
    
            $facture = Cache::remember("facture", 3600, function () use ($sousUtilisateurId, $userId, $num_facture) {
               
           return Facture::where('num_facture', $num_facture)
                ->with(['client', 'articles.article', 'Etiquettes.etiquette', 'echeances', 'factureAccompts', 'paiement'])
                ->where(function($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->first();
            });
            if (!$facture) {
                $facture = Cache::remember("facture", 3600, function () use ($sousUtilisateurId, $userId, $num_facture) {
                    
                return FactureAvoir::where('num_facture', $num_facture)
                    ->with(['client', 'articles.article', 'Etiquettes.etiquette'])
                    ->where(function($query) use ($sousUtilisateurId, $userId) {
                        $query->where('sousUtilisateur_id', $sousUtilisateurId)
                              ->orWhere('user_id', $userId);
                    })
                    ->first();
                });
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $facture = Cache::remember("facture", 3600, function () use ($userId, $num_facture) {
            
            return Facture::where('num_facture', $num_facture)
                ->with(['client', 'articles.article', 'Etiquettes.etiquette', 'echeances', 'factureAccompts', 'paiement'])
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
            });
    
            if (!$facture) {
                $facture = Cache::remember("facture", 3600, function () use ($userId, $num_facture) {
                
               return FactureAvoir::where('num_facture', $num_facture)
                    ->with(['client', 'articles.article', 'Etiquettes.etiquette'])
                    ->where('user_id', $userId)
                    ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                        $query->where('id_user', $userId);
                    })
                    ->first();
                });
            }
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        // Vérifier si la facture est trouvée
        if (!$facture) {
            return response()->json(['error' => 'Facture non trouvée'], 404);
        }
    
        // Initialiser la réponse commune
        $response = [
            'id' => $facture->id,
            'numero_facture' => $num_facture,
            'date_creation' => $facture->date_creation ? Carbon::parse($facture->date_creation)->format('Y-m-d H:i:s') : $facture->date,
            'client' => [
                'nom' => $facture->client->nom_client,
                'prenom' => $facture->client->prenom_client,
                'adresse' => $facture->client->adress_client,
                'telephone' => $facture->client->tel_client,
                'nom_entreprise'=> $facture->client->nom_entreprise,
            ],
            'prix_HT' => $facture->prix_HT,
            'prix_TTC' => $facture->prix_TTC,
            'articles' => [],
            'echeances' => [],
            'factures_accomptes' => [],

            'etiquettes' => ($facture->Etiquettes ?? collect())->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all(), 
        ];
    
        // Ajouter des champs spécifiques aux factures simples
        if ($facture instanceof Facture) {
            $response['note_facture'] = $facture->note_fact;
            $response['type_paiement'] = $facture->type_paiement;
            $response['moyen_paiement'] = $facture->paiement->nom_payement ?? null;
            $response['nombre_echeance'] = $facture->echeances ? $facture->echeances->count() : 0;
            $response['type']='simple';
    
            // Ajouter les articles
            foreach ($facture->articles as $articleFacture) {
                $response['articles'][] = [
                    'id_article' => $articleFacture->id_article,
                    'nom_article' => $articleFacture->article->nom_article,
                    'TVA' => $articleFacture->TVA_article,
                    'quantite_article' => $articleFacture->quantite_article,
                    'prix_unitaire_article' => $articleFacture->prix_unitaire_article,
                    'prix_total_tva_article' => $articleFacture->prix_total_tva_article,
                    'prix_total_article' => $articleFacture->prix_total_article,
                    'reduction_article' => $articleFacture->reduction_article,
                ];
            }
    
            // Ajouter les échéances
            foreach ($facture->echeances as $echeance) {
                $response['echeances'][] = [
                    'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                    'montant_echeance' => $echeance->montant_echeance,
                ];
            }
    
            // Ajouter les factures d'acomptes
            foreach ($facture->factureAccompts as $factureAccomp) {
                $response['factures_accomptes'][] = [
                    'titreAccomp' => $factureAccomp->titreAccomp,
                    'dateAccompt' => Carbon::parse($factureAccomp->dateAccompt)->format('Y-m-d'),
                    'dateEcheance' => Carbon::parse($factureAccomp->dateEcheance)->format('Y-m-d'),
                    'montant' => $factureAccomp->montant,
                    'commentaire' => $factureAccomp->commentaire,
                ];
            }
        } elseif ($facture instanceof FactureAvoir) {
            $response['date'] = $facture->date;
            $response['titre'] = $facture->titre;
            $response['description'] = $facture->description;
            $response['commentaire'] = $facture->commentaire;
            $response['doc_externe'] = $facture->doc_externe;
            $response['type']='avoir';
            
            // Ajouter les articles
            foreach ($facture->articles as $articleFacture) {
                $response['articles'][] = [
                    'id_article' => $articleFacture->id_article,
                    'nom_article' => $articleFacture->article->nom_article,
                    'TVA' => $articleFacture->TVA_article,
                    'quantite_article' => $articleFacture->quantite_article,
                    'prix_unitaire_article' => $articleFacture->prix_unitaire_article,
                    'prix_total_tva_article' => $articleFacture->prix_total_tva_article,
                    'prix_total_article' => $articleFacture->prix_total_article,
                    'reduction_article' => $articleFacture->reduction_article,
                ];
            }
        }
    
        // Retourner la réponse JSON
        return response()->json(['facture_details' => $response], 200);
    }
    
    
     
       

}