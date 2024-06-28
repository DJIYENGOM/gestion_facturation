<?php

namespace App\Http\Controllers;

use App\Models\ArtcleFacture;
use App\Models\ArticleBonCommande;
use App\Models\BonCommande;
use App\Models\Echeance;
use App\Models\Facture;
use App\Models\FactureAccompt;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
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
            'statut_commande'=> 'nullable|in:en_attente,transformer,valider,annuler,brouillon',
            'echeances' => 'nullable|array',
            'echeances.*.date_pay_echeance' => 'required|date',
            'echeances.*.montant_echeance' => 'required|numeric|min:0',

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

        // Création de la facture
        $commande = BonCommande::create([
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
        ]);
    
        $commande->num_commande = BonCommande::generateNumBoncommande($commande->id);
        $commande->save();
    
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

        return response()->json(['message' => 'commande créée avec succès', 'commande' => $commande], 201);

    }

    public function TransformeBonCommandeEnFacture(Request $request, $id)
{
    $BonCommande = BonCommande::find($id);
    if (!$BonCommande) {
        return response()->json(['error' => 'BonCommande non trouvé'], 404);
    }

    $validator = Validator::make($request->all(), [
        'client_id' => 'required|exists:clients,id',
        'note_fact' => 'nullable|string',
        'reduction_facture' => 'nullable|numeric',
        'active_Stock'=> 'nullable|in:oui,non',
        'prix_HT'=> 'required|numeric',
        'prix_TTC'=>'required|numeric',
        'type_paiement' => 'required|in:immediat,echeance,facture_Accompt',
        'id_paiement' => 'nullable|required_if:type_paiement,immediat|exists:payements,id',
        'echeances' => 'nullable|required_if:type_paiement,echeance|array',
        'echeances.*.date_pay_echeance' => 'required|date',
        'echeances.*.montant_echeance' => 'required|numeric|min:0',
        'facture_accompts' => 'nullable|required_if:type_paiement,facture_Accompt|array',
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
    // Création de la facture
    $facture = Facture::create([
        'client_id' => $request->client_id,
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

        'BonCommande_id'=>$BonCommande->id,
    ]);

    $facture->num_fact = Facture::generateNumFacture($facture->id);
    $facture->save();

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
                'date_reçu' => now(),
                'montant' => $facture->prix_TTC,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        
    }

    $BonCommande->statut_commande = 'transformer';
    $BonCommande->save();

    return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture], 201);
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
        'prenom client' => $BonCommande->client->prenom_client, 
        'nom client' => $BonCommande->client->nom_client, 
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
}
