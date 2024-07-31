<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Devi;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\ArticleDevi;
use App\Models\BonCommande;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\ArticleBonCommande;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;


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
            'note_devi' => $request->input('note_fact'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'statut_devi' => $request->statut_devi ?? 'en_attente',
        ]);
        
            $devi->save();
            NumeroGeneratorService::incrementerCompteur($userId, 'devis');

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
    $devi = Devi::find($deviId);
    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

    $devi->statut_devi = 'transformer';
    $devi->save();

    return response()->json(['message' => 'Devi transformée avec succès', 'Devi' => $devi], 200);
}

public function TransformeDeviEnBonCommande($deviId)
{
    $devi = Devi::find($deviId);
    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
    }

    $devi->statut_devi = 'transformer';
    $devi->save();

    return response()->json(['message' => 'Devi transformée avec succès', 'Devi' => $devi], 200);
}

public function annulerDevi($deviId)
{
    $devi = Devi::find($deviId);

    if (!$devi) {
        return response()->json(['error' => 'Devi non trouvé'], 404);
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

    // Mettre à jour le statut du devis en "annuler"
    $devi->statut_devi = 'annuler';
    $devi->save();

    return response()->json(['message' => 'Devi annulé avec succès', 'devi' => $devi], 200);
}

public function supprimerDevi($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $devi = Devi::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($devi){
                $devi->archiver = 'oui';
                $devi->save();
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
                return response()->json(['message' => 'devi supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet devi'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function listerToutesDevi()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $devis = devi::with('client')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $devis = Devi::with('client')
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
        'prenom_client' => $devi->client->prenom_client, 
        'nom_client' => $devi->client->nom_client, 
        'active_Stock' => $devi->active_Stock,
        'reduction_devi' => $devi->reduction_devi,
    ];
}

return response()->json(['devis' => $response]);
}


public function DetailsDevis($id)
{
    // Rechercher la facture par son numéro
    $devi = Devi::where('id', $id)
                ->with(['client', 'articles.article', 'echeances', 'factureAccompts'])
                ->first();

    // Vérifier si la devi existe
    if (!$devi) {
        return response()->json(['error' => 'devi non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $dateCreation = Carbon::parse($devi->date_devi);

    // Préparer la réponse
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
    ];

    // Vérifier si 'articles' est non nul et une collection
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

    // Vérifier si 'echeances' est non nul et une collection
    if ($devi->echeances && $devi->echeances->isNotEmpty()) {
        foreach ($devi->echeances as $echeance) {
            $response['echeances'][] = [
                'date_pay_echeance' => Carbon::parse($echeance->date_pay_echeance)->format('Y-m-d'),
                'montant_echeance' => $echeance->montant_echeance,
            ];
        }
    }

    // Vérifier si 'factureAccompts' est non nul et une collection
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

    // Retourner la réponse JSON
    return response()->json(['devi_details' => $response], 200);
}
}
  

