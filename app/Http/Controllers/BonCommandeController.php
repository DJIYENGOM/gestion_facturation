<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\BonCommande;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\ArticleBonCommande;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;


class BonCommandeController extends Controller
{
    public function creerBonCommande(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_commande' => 'nullable|string',
            'reduction_commande' => 'nullable|numeric',
            'date_commande'=>'required|date',
            'date_limite_commande'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'active_Stock'=> 'nullable|in:oui,non',
            'statut_commande'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',
            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'nullable|date',
            'echeances.*.montant_echeance' => 'nullable|numeric|min:0',

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

        $typeDocument = 'commande';
        $numBonCommande= NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $commande = BonCommande::create([
            'num_commande' => $numBonCommande,
            'client_id' => $request->client_id,
            'date_commande' => $request->date_commande,
            'date_limite_commande' => $request->date_limite_commande,
            'reduction_commande' => $request->input('reduction_commande', 0),
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_commande' => $request->input('note_commande'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_commande' => $request->statut_commande ?? 'en_attente',
            'active_Stock' => $request->active_Stock ?? 'oui',
        ]);
    
        $commande->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'commande');

    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleBonCommande::create([
                'id_BonCommande' => $commande->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
        if ($request->has('echeances')) {
            foreach ($request->echeances as $echeanceData) {
                Echeance::create([
                    'bonCommande_id' => $commande->id,
                    'date_pay_echeance' => $echeanceData['date_pay_echeance'],
                    'montant_echeance' => $echeanceData['montant_echeance'],
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
            }
        }

        if ($commande->active_Stock == 'oui') {
            foreach ($commande->articles as $article) {
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
                    $stock->facture_id = null;
                    $stock->bonCommande_id = $commande->id;
                    $stock->livraison_id = null;
                    $stock->sousUtilisateur_id = $sousUtilisateurId;
                    $stock->user_id = $userId;
                    $stock->save();
                }
            }
        }
        

        return response()->json(['message' => 'commande créée avec succès', 'commande' => $commande], 201);

    }

    public function TransformeBonCommandeEnFacture($id)
{
    $BonCommande = BonCommande::find($id);
    if (!$BonCommande) {
        return response()->json(['error' => 'BonCommande non trouvé'], 404);
    }
    $BonCommande->statut_commande = 'transformer';
    $BonCommande->save();
    return response()->json(['message' => 'BonCommande transformée avec succès', 'BonCommande' => $BonCommande], 200);

}

public function annulerBonCommande($id)
{
    $BonCommande = BonCommande::find($id);

    if (!$BonCommande) {
        return response()->json(['error' => 'BonCommande non trouvé'], 404);
    }

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Mettre à jour le statut du BonCommandes en "annuler"
    $BonCommande->statut_commande = 'annuler';
    $BonCommande->save();

    return response()->json(['message' => 'BonCommande annulé avec succès', 'BonCommande' => $BonCommande], 200);
}

public function listerTousBonCommande()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $BonCommandes = BonCommande::with('client')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommandes = BonCommande::with('client')
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
// Construire la réponse avec les détails des BonCommandes et les noms des clients
$response = [];
foreach ($BonCommandes as $BonCommande) {
    $response[] = [
        'id' => $BonCommande->id,
        'num_BonCommande' => $BonCommande->num_commande,
        'date_BonCommande' => $BonCommande->date_commande,
        'statut_BonCommande' => $BonCommande->statut_commande,
        'date_limite' => $BonCommande->date_limite_commande,
        'prix_Ht' => $BonCommande->prix_HT,
        'prix_Ttc' => $BonCommande->prix_TTC,
        'note_BonCommande' => $BonCommande->note_commande,
        'prenom_client' => $BonCommande->client->prenom_client, 
        'nom_client' => $BonCommande->client->nom_client, 
        'active_Stock' => $BonCommande->active_Stock,
        'reduction_commande' => $BonCommande->reduction_commande,
    ];
}

return response()->json(['BonCommandes' => $response]);
}

public function supprimerBonCommande($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $BonCommande = BonCommande::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($BonCommande){
                $BonCommande->archiver = 'oui';
                $BonCommande->save();
            return response()->json(['message' => 'BonCommande supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet BonCommande'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $BonCommande = BonCommande::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($BonCommande){
                    $BonCommande->archiver = 'oui';
                    $BonCommande->save();
                return response()->json(['message' => 'BonCommande supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet BonCommande'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function DetailsBonCommande($id)
{
    // Rechercher la facture par son numéro
    $bonCommande = BonCommande::where('id', $id)
                ->with(['client', 'articles.article', 'echeances'])
                ->first();

    // Vérifier si la bonCommande existe
    if (!$bonCommande) {
        return response()->json(['error' => 'bonCommande non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($bonCommande->date_commande);

    // Préparer la réponse
    $response = [
        'id_bonCommande' => $bonCommande->id,
        'numero_bonCommande' => $bonCommande->num_commande,
        'date_creation' => $dateCreation->format('Y-m-d H:i:s'),
        'date_limite' => $bonCommande->date_limite_commande,
        'client' => [
            'id' => $bonCommande->client->id,
            'nom' => $bonCommande->client->nom_client,
            'prenom' => $bonCommande->client->prenom_client,
            'adresse' => $bonCommande->client->adress_client,
            'telephone' => $bonCommande->client->tel_client,
            'nom_entreprise'=> $bonCommande->client->nom_entreprise,
        ],
        'note_bonCommande' => $bonCommande->note_commande,
        'prix_HT' => $bonCommande->prix_HT,
        'prix_TTC' => $bonCommande->prix_TTC,
        'reduction_bonCommande' => $bonCommande->reduction_commande,
        'statut_bonCommande' => $bonCommande->statut_commande,
        'nom_comptable' => $bonCommande->compteComptable->nom_compte_comptable ?? null,
        'articles' => [],
        'echeances' => [],
        'nombre_echeance' => $bonCommande->echeances ? $bonCommande->echeances->count() : 0,
        'active_Stock' => $bonCommande->active_Stock,
    ];

    // Vérifier si 'articles' est non nul et une collection
    if ($bonCommande->articles && $bonCommande->articles->isNotEmpty()) {
        foreach ($bonCommande->articles as $articlebonCommande) {
            $response['articles'][] = [
                'id_article' => $articlebonCommande->id_article,
                'nom_article' => $articlebonCommande->article->nom_article,
                'TVA' => $articlebonCommande->TVA_article,
                'quantite_article' => $articlebonCommande->quantite_article,
                'prix_unitaire_article' => $articlebonCommande->prix_unitaire_article,
                'prix_total_tva_article' => $articlebonCommande->prix_total_tva_article,
                'prix_total_article' => $articlebonCommande->prix_total_article,
                'reduction_article' => $articlebonCommande->reduction_article,
            ];
        }
    }

    // Vérifier si 'echeances' est non nul et une collection
    if ($bonCommande->echeances && $bonCommande->echeances->isNotEmpty()) {
        foreach ($bonCommande->echeances as $echeance) {
            $response['echeances'][] = [
                'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                'montant_echeance' => $echeance->montant_echeance,
            ];
        }
    }

    // Retourner la réponse JSON
    return response()->json(['bonCommande_details' => $response], 200);
}
}
