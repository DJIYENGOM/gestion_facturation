<?php

namespace App\Http\Controllers;

use App\Models\GrilleTarifaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrilleTarifaireController extends Controller
{
    public function creerGrilleTarifaire(Request $request)
    {
        
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'idArticle' => 'required|exists:articles,id',
            'idClient' => 'required|exists:clients,id',
            'montantTarif' => 'required|numeric|min:0',
            'tva' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tva = $request->tva ?? 0;
        $montantTva = $request->montantTarif * ((100 + $tva)/100);

        $grilleTarifaire = new GrilleTarifaire([
            'idArticle' => $request->idArticle,
            'idClient' => $request->idClient,
            'montantTarif' => $request->montantTarif,
            'tva' => $tva,
            'montantTva' => $montantTva,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);

        $grilleTarifaire->save();

        return response()->json(['message' => 'Grille tarifaire créée avec succès', 'grille_tarifaire' => $grilleTarifaire]);
    }

    public function listerTariPourClientSurArticle($clientId,$article)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $grillesTarifaires = GrilleTarifaire::with(['client', 'article'])
            ->where(function ($query) use ($sousUtilisateurId, $userId) {
                $query->where('sousUtilisateur_id', $sousUtilisateurId)
                      ->orWhere('user_id', $userId);
            })
                -> where('idClient', $clientId)->where('idArticle', $article) ->get(); 

        } elseif (auth()->check()) {
            $userId = auth()->id();
            $grillesTarifaires = GrilleTarifaire::with(['client', 'article'])
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                              $query->where('id_user', $userId);
                 });
                })
                -> where('idClient', $clientId)->where('idArticle', $article) ->get();

            } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $response = [];
        foreach ($grillesTarifaires as $grille) {
            $response[] = [
                'nom_client' => $grille->client->nom_client,
                'prenom_client' => $grille->client->prenom_client, 
                'nom_article' => $grille->article->nom_article, 
                'montant_tarif' => $grille->montantTarif,
                'tva' => $grille->tva,
                'montant_tva' => $grille->montantTva,
            ];
        }
    
        return response()->json(['grilles_tarifaires' => $response]);
    }
    
    public function modifierGrilleTarifaire(Request $request, $idTarif)
    {
        $grilleTarifaire = GrilleTarifaire::findOrFail($idTarif);
    
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            if ($grilleTarifaire->sousUtilisateur_id !== $sousUtilisateurId) {
                return response()->json(['error' => 'Cette sous-utilisateur ne peut pas modifier ce grille tarifaire car il ne l\'a pas créé'], 401);
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();
            if ($grilleTarifaire->user_id !== $userId) {
                return response()->json(['error' => 'Cet utilisateur ne peut pas modifier ce grille tarifaire car il ne l\'a pas créé'], 401);
            }
        } else {
            return response()->json(['error' => 'Non autorisé'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'montantTarif' => 'required|numeric|min:0',
            'tva' => 'nullable|numeric|min:0|max:100',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Mise à jour des champs
        $grilleTarifaire->montantTarif = $request->montantTarif;
        $grilleTarifaire->tva = $request->tva;
        $grilleTarifaire->montantTva = $grilleTarifaire->montantTarif * ($grilleTarifaire->tva / 100);
    
        $grilleTarifaire->save();
    
        return response()->json(['message' => 'Grille tarifaire modifiée avec succès', 'grille_tarifaire' => $grilleTarifaire]);
    }
    

    public function supprimerGrilleTarifaire($idTarif)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
           $grilleTarifaire = GrilleTarifaire::where('id',$idTarif)
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();
                
                if($grilleTarifaire){
                    $grilleTarifaire->delete();
                return response()->json(['message' => 'grilleTarifaire supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'ce sous utilisateur ne peut pas modifier ce grilleTarifaire'], 401);
                }
    
        }elseif (auth()->check()) {
            $userId = auth()->id();
    
            $grilleTarifaire = GrilleTarifaire::where('id',$idTarif)
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
                if($grilleTarifaire){
                    $grilleTarifaire->delete();
                    return response()->json(['message' => 'grilleTarifaire supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'cet utilisateur ne peut pas modifier ce grilleTarifaire'], 401);
                }
    
        }else {
            return response()->json(['error' => 'Unauthorizedd'], 401);
        }

    }
}
