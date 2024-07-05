<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\ArtcleFacture;
use Illuminate\Http\Request;
use App\Models\Echeance;
use App\Models\FactureAccompt;
use App\Models\PaiementRecu;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Services\NumeroGeneratorService;



class FactureController extends Controller
{
    public function creerFacture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_fact' => 'nullable|string',
            'reduction_facture' => 'nullable|numeric',
            'active_Stock' => 'nullable|in:oui,non',
            'prix_HT' => 'required|numeric',
            'prix_TTC' => 'required|numeric',
            'date_creation' => 'nullable|date',
            'type_paiement' => 'required|in:immediat,echeance,facture_Accompt',
            'statut_paiement' => 'nullable|in:en_attente,brouillon,payer',
            'id_paiement' => 'nullable|required_if:type_paiement,immediat|exists:payements,id',
            'echeances' => 'nullable|required_if:type_paiement,echeance|array',
            'echeances.*.date_pay_echeance' => 'required|date',
            'echeances.*.montant_echeance' => 'required|numeric|min:0',
            'articles' => 'required|array',
            'articles.*.id_article' => 'required|exists:articles,id',
            'articles.*.quantite_article' => 'required|integer',
            'articles.*.prix_unitaire_article' => 'required|numeric',
            'articles.*.TVA_article' => 'nullable|numeric',
            'articles.*.reduction_article' => 'nullable|numeric',
            'articles.*.prix_total_article' => 'nullable|numeric',
            'articles.*.prix_total_tva_article' => 'nullable|numeric'
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
    
        $datePaiement = $request->type_paiement === 'immediat' ? now() : null;
    
        // Déterminer le statut de paiement initial
        $statutPaiement = $request->type_paiement === 'facture_Accompt' ? 'brouillon' :'en_attente';
    
        $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
    
        // Création de la facture
        $facture = Facture::create([
            'num_fact' => $numFacture,
            'client_id' => $request->client_id,
            'date_creation' => $request->input('date_creation') ?? now(),
            'date_paiement' => $datePaiement,
            'reduction_facture' => $request->input('reduction_facture', 0),
            'active_Stock' => $request->active_Stock ?? 'oui',
            'prix_HT' => $request->prix_HT,
            'prix_TTC' => $request->prix_TTC,
            'note_fact' => $request->input('note_fact'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'type_paiement' => $request->type_paiement,
            'statut_paiement' => $statutPaiement,
            'id_paiement' => $request->type_paiement === 'immediat' ? $request->id_paiement : null,
        ]);
    
    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
            $prixTotalArticle = $articleData['prix_total_article'];
            $prixTotalArticleTva = $articleData['prix_total_tva_article'];
    
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
        if ($request->type_paiement === 'echeance') {
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
        if ($request->type_paiement === 'facture_Accompt') {   
            $facture->statut_paiement = 'brouillon';
            $facture->save();
        }
    
        // Mettre à jour le statut de la facture si elle est initialement en brouillon
        if ($facture->type_paiement === 'facture_Accompt' && $facture->statut_paiement === 'brouillon') {
            $facture->statut_paiement = 'en_attente';
            $facture->update();

        }elseif($facture->type_paiement === 'immediat'){
            $facture->statut_paiement = 'payer';
            $facture->save();

        }else{
            $facture->statut_paiement = 'en_attente';
            $facture->save();
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
            'Montant total' => $article->prix_total_article,
            'Montant total avec TVA' => $article->prix_total_tva_article,
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
        'num_fact' => $facture->num_fact,
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

public function DetailsFacture($id)
{
    // Rechercher la facture par son numéro
    $facture = Facture::where('id', $id)
                ->with(['client', 'articles.article', 'echeances', 'factureAccompts','paiement'])
                ->first();

    // Vérifier si la facture existe
    if (!$facture) {
        return response()->json(['error' => 'Facture non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($facture->date_creation);

    // Préparer la réponse
    $response = [
        'numero_facture' => $facture->num_fact,
        'date_creation' => $dateCreation->format('Y-m-d H:i:s'),
        'client' => [
            'nom' => $facture->client->nom_client,
            'prenom' => $facture->client->prenom_client,
        ],
        'note_facture' => $facture->note_fact,
        'prix_HT' => $facture->prix_HT,
        'prix_TTC' => $facture->prix_TTC,
        'type_paiement' => $facture->type_paiement,
        'moyen_paiement' => $facture->paiement->nom_payement ?? null,
        'articles' => [],
        'echeances' => [],
        'nombre_echeance' => $facture->echeances ? $facture->echeances->count() : 0,
        'factures_accomptes' => [],
    ];

    // Vérifier si 'articles' est non nul et une collection
    if ($facture->articles && $facture->articles->isNotEmpty()) {
        foreach ($facture->articles as $articleFacture) {
            $response['articles'][] = [
                'nom_article' => $articleFacture->article->nom_article,
                'TVA' => $articleFacture->TVA_article,
                'quantite_article' => $articleFacture->quantite_article,
                'prix_unitaire_article' => $articleFacture->prix_unitaire_article,
                'prix_total_tva_article' => $articleFacture->prix_total_tva_article,
                'reduction_article' => $articleFacture->reduction_article,
            ];
        }
    }

    // Vérifier si 'echeances' est non nul et une collection
    if ($facture->echeances && $facture->echeances->isNotEmpty()) {
        foreach ($facture->echeances as $echeance) {
            $response['echeances'][] = [
                'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                'montant_echeance' => $echeance->montant_echeance,
            ];
        }
    }

    // Vérifier si 'factureAccompts' est non nul et une collection
    if ($facture->factureAccompts && $facture->factureAccompts->isNotEmpty()) {
        foreach ($facture->factureAccompts as $factureAccomp) {
            $response['factures_accomptes'][] = [
                'titreAccomp' => $factureAccomp->titreAccomp,
                'dateAccompt' => Carbon::parse($factureAccomp->dateAccompt)->format('Y-m-d'),
                'dateEcheance' => Carbon::parse($factureAccomp->dateEcheance)->format('Y-m-d'),
                'montant' => $factureAccomp->montant,
                'commentaire' => $factureAccomp->commentaire,
            ];
        }
    }

    // Retourner la réponse JSON
    return response()->json(['facture_details' => $response], 200);
}



}