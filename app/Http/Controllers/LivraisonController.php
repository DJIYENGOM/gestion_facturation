<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Livraison;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ArtcleFacture;
use App\Models\FactureAccompt;
use App\Models\ArticleLivraison;
use App\Services\NumeroGeneratorService;
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
        $typeDocument = 'livraison';
        $numlivraison = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        // Création de la livraison
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
            'num_livraison' => $numlivraison
        ]);
    
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
                        $query->where('user_id', $userId);
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
        'prenom_client' => $livraison->client->prenom_client, 
        'nom_client' => $livraison->client->nom_client, 
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

    $livraison->statut_livraison = 'livrer';
    $livraison->save();


    return response()->json(['message' => 'livraison transformée en facture avec succès'], 201);
}

public function DetailsLivraison($id)
{
    // Rechercher la facture par son numéro
    $livraison = Livraison::where('id', $id)
                ->with(['client', 'articles.article'])
                ->first();

    // Vérifier si la livraison existe
    if (!$livraison) {
        return response()->json(['error' => 'livraison non trouvée'], 404);
    }

    // Convertir date_creation en instance de Carbon si ce n'est pas déjà le cas
    $date_livraison = Carbon::parse($livraison->date_livraison);

    // Préparer la réponse
    $response = [
        'id_livraison' => $livraison->id,
        'numero_livraison' => $livraison->num_livraison,
        'date_livraison' => $date_livraison->format('Y-m-d H:i:s'),
        'client' => [
            'id' => $livraison->client->id,
            'nom' => $livraison->client->nom_client,
            'prenom' => $livraison->client->prenom_client,
            'adresse' => $livraison->client->adress_client,
            'telephone' => $livraison->client->tel_client,
            'nom_entreprise'=> $livraison->client->nom_entreprise,
        ],
        'note_livraison' => $livraison->note_livraison,
        'prix_HT' => $livraison->prix_HT,
        'prix_TTC' => $livraison->prix_TTC,
        'reduction_livraison' => $livraison->reduction_livraison,
        'statut_livraison' => $livraison->statut_livraison,
        'nom_comptable' => $livraison->compteComptable->nom_compte_comptable ?? null,
        'articles' => [],
        'active_Stock' => $livraison->active_Stock,
    ];

    // Vérifier si 'articles' est non nul et une collection
    if ($livraison->articles && $livraison->articles->isNotEmpty()) {
        foreach ($livraison->articles as $articlelivraison) {
            $response['articles'][] = [
                'id_article' => $articlelivraison->id_article,
                'nom_article' => $articlelivraison->article->nom_article,
                'TVA' => $articlelivraison->TVA_article,
                'quantite_article' => $articlelivraison->quantite_article,
                'prix_unitaire_article' => $articlelivraison->prix_unitaire_article,
                'prix_total_tva_article' => $articlelivraison->prix_total_tva_article,
                'prix_total_article' => $articlelivraison->prix_total_article,
                'reduction_article' => $articlelivraison->reduction_article,
            ];
        }
    }

    // Retourner la réponse JSON
    return response()->json(['bonCommande_details' => $response], 200);
}
}
