<?php

namespace App\Http\Controllers;

use App\Models\ArtcleFacture;
use App\Models\ArticleLivraison;
use App\Models\Echeance;
use App\Models\Facture;
use App\Models\FactureAccompt;
use App\Models\Livraison;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class LivraisonController extends Controller
{
    public function ajouterLivraison(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'note_livraison' => 'nullable|string',
            'reduction_livraison' => 'nullable|numeric',
            'date_livraison'=>'required|date',
            'prix_HT'=> 'required|numeric',
            'prix_TTC'=>'required|numeric',
            'statut_livraison'=> 'nullable|in:brouillon, preparer, planifier,livrer,annuler',
           
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
        $livraison = Livraison::create([
            'client_id' => $request->client_id,
            'date_livraison' => $request->date_livraison,
            'reduction_livraison' => $request->input('reduction_livraison', 0),
            'statut_livraison' => $request->statut_livraison ?? 'brouillon',
            'prix_HT'=>$request->prix_HT,
            'prix_TTC' =>$request->prix_TTC,
            'note_livraison' => $request->input('note_livraison'),
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    
        $livraison->num_livraison =Livraison::generateNumLivraison($livraison->id);
        $livraison->save();
    
        // Ajouter les articles à la facture
        foreach ($request->articles as $articleData) {
            $quantite = $articleData['quantite_article'];
            $prixUnitaire = $articleData['prix_unitaire_article'];
            $TVA = $articleData['TVA_article'] ?? 0;
            $reduction = $articleData['reduction_article'] ?? 0;
        
            $prixTotalArticle=$articleData['prix_total_article'];
            $prixTotalArticleTva=$articleData['prix_total_tva_article'];

            ArticleLivraison::create([
                'id_livraison' => $livraison->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $quantite,
                'prix_unitaire_article' => $prixUnitaire,
                'TVA_article' => $TVA,
                'reduction_article' => $reduction,
                'prix_total_article' => $prixTotalArticle,
                'prix_total_tva_article' => $prixTotalArticleTva,
            ]);
        }
        return response()->json(['message' => 'livraison créée avec succès', 'livraison' => $livraison], 201);

    }

    public function listerToutesLivraisons()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $livraisons = Livraison::with('client')
            ->where('archiver', 'non')
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                    ->orWhere('user_id', $userId);
            })
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $livraisons = Livraison::with('client')
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
// Construire la réponse avec les détails des livraisons et les noms des clients
$response = [];
foreach ($livraisons as $livraison) {
    $response[] = [
        'id' => $livraison->id,
        'num_livraison' => $livraison->num_livraison,
        'date_livraison' => $livraison->date_livraison,
        'statut_livraison' => $livraison->statut_livraison,
        'prix_Ht' => $livraison->prix_HT,
        'prix_Ttc' => $livraison->prix_TTC,
        'note_livraison' => $livraison->note_livraison,
        'prenom client' => $livraison->client->prenom_client, 
        'nom client' => $livraison->client->nom_client, 
        'active_Stock' => $livraison->active_Stock,
        'reduction_livraison' => $livraison->reduction_livraison,
    ];
}

return response()->json(['livraisons' => $response]);
}

public function supprimerLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->archiver = 'oui';
                $livraison->save();
            return response()->json(['message' => 'livraison supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->archiver = 'oui';
                    $livraison->save();
                return response()->json(['message' => 'livraison supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function PlanifierLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'planifier';
                $livraison->save();
            return response()->json(['message' => 'livraison planifier avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas planifier cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'planifier';
                    $livraison->save();
                return response()->json(['message' => 'livraison planifier avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas planifier cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function RealiserLivraison($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'livrer';
                $livraison->save();
            return response()->json(['message' => 'livraison realiser avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas realiser cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'livrer';
                    $livraison->save();
                return response()->json(['message' => 'livraison realiser avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas realiser cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function LivraisonPreparer($id)
{    
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $livraison = Livraison::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            if($livraison){
                $livraison->statut_livraison = 'preparer';
                $livraison->save();
            return response()->json(['message' => 'livraison preparer avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas preparer cet livraison'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $livraison = Livraison::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            
                if($livraison){
                    $livraison->statut_livraison = 'preparer';
                    $livraison->save();
                return response()->json(['message' => 'livraison preparer avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas preparer cet livraison'], 401);
            }

    }else {
        return response()->json(['error' => 'Unauthorizedd'], 401);
    }

}

public function transformerLivraisonEnFacture(Request $request, $id)
{
    $livraison = Livraison::find($id);
    if (!$livraison) {
        return response()->json(['error' => 'livraison non trouvé'], 404);
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

        'livraison_id'=>$livraison->id,
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

    $livraison->statut_livraison = 'livrer';
    $livraison->save();

    return response()->json(['message' => 'Facture créée avec succès', 'facture' => $facture], 201);
}

}
