<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ConfigurationRelanceAuto;
use Illuminate\Support\Facades\Validator;

class EcheanceController extends Controller
{
    public function creerEcheance(Request $request, $factureId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = null;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        $validator = Validator::make($request->all(), [
            'date_pay_echeance' => 'required|date',
            'montant_echeance' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $facture = Facture::find($factureId);
        if (!$facture) {
            return response()->json(['error' => 'Facture non trouvée'], 404);
        }
    
        // Créer l'échéance
        $echeance = Echeance::create([
            'facture_id' => $factureId,
            'date_pay_echeance' => $request->date_pay_echeance,
            'montant_echeance' => $request->montant_echeance,
            'commentaire' => $request->commentaire,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
        ]);

        Echeance::envoyerNotificationSEcheanceImpayer($echeance);

        return response()->json(['message' => 'Échéance créée avec succès', 'echeance' => $echeance], 201);
    }

    public function listEcheanceParFacture($factureId)
    {
      
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Accès non autorisé'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        Facture::findOrFail($factureId)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId);
            $echeances = Echeance::where('facture_id', $factureId)->get();

    } elseif (auth()->check()) {
        $userId = auth()->id();

        Facture::findOrFail($factureId)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            });
            $echeances = Echeance::where('facture_id', $factureId)->get();

    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

        return response()->json(['echeances' => $echeances], 200);
    }

    public function supprimerEcheance($echeanceId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
           $echeance = Echeance::where('id',$echeanceId)
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();
            if ($echeance)
                {
                    $echeance->delete();
                return response()->json(['message' => 'echeance supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimé cet echeance'], 401);
                }
    
        }elseif (auth()->check()) {
            $userId = auth()->id();
    
            $echeance = Echeance::where('id',$echeanceId)
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
    
                if ($echeance)
                {
                    $echeance->delete();
                    return response()->json(['message' => 'echeance supprimé avec succès']);
                }else {
                    return response()->json(['error' => 'cet utilisateur ne peut pas supprimé cet echeance'], 401);
                }
    
        }else {
            return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
        }
    }

    public function modifierEcheance(Request $request, $echeanceId)
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

    $validator = Validator::make($request->all(), [
        'date_pay_echeance' => 'required|date',
        'montant_echeance' => 'required|numeric|min:0',
        'commentaire' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $echeance = Echeance::find($echeanceId);
    if (!$echeance) {
        return response()->json(['error' => 'Échéance non trouvée'], 404);
    }

    // Vérifiez que l'utilisateur a accès à l'échéance
    if ($echeance->sousUtilisateur_id && $echeance->sousUtilisateur_id !== $sousUtilisateurId) {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }
    if ($echeance->user_id && $echeance->user_id !== $userId) {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Mettre à jour les informations de l'échéance
    $echeance->update([
        'date_pay_echeance' => $request->date_pay_echeance,
        'montant_echeance' => $request->montant_echeance,
        'commentaire' => $request->commentaire,
    ]);

    Echeance::envoyerNotificationSEcheanceImpayer($echeance);

    return response()->json(['message' => 'Échéance mise à jour avec succès', 'echeance' => $echeance], 200);
}


public function transformerEcheanceEnPaiementRecu(Request $request, $EcheanceId)
{
    $Echeance = Echeance::find($EcheanceId);
    if (!$Echeance) {
        return response()->json(['error' => 'Echeance non trouvé'], 404);
    }
    if($Echeance->facture_id==Null) {
        return response()->json(['error' => 'cette echeance doit etre associer à une facture pour etre transformée en paiement recu'], 401);
    }

    $validator = Validator::make($request->all(), [

        //'facture_id' => 'required|exists:factures,id',
        'num_paiement' => 'nullable|string|max:255',
        'date_prevu' => 'nullable|date',
        'date_recu' => 'nullable|date',
        'montant' => 'required|numeric|min:0',
        'commentaire' => 'nullable|string',
        'id_paiement' => 'required|exists:payements,id',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $paiementRecu = PaiementRecu::create([
        'facture_id' => $Echeance->facture_id,
        'num_paiement' => $request->input('num_paiement'),
        'date_prevu' => $request->input('date_prevu'),
        'date_recu' => $request->input('date_recu'),
        'montant' => $request->input('montant'),
        'commentaire' => $request->input('commentaire'),
        'id_paiement' => $request->input('id_paiement'),
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    $facture=Facture::find($Echeance->facture_id);
    $facture->update([
        'statut_paiement' =>'payer',
    ]);
    
    $Echeance->delete();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Echeances ont été transformées en payment Reçu',
        'id_facture' => $facture->id
    ]);

    return response()->json([
        'message' => 'Echeance transformée en payment Recu avec succès',
        'paymentRecu' => $paiementRecu
    ], 201);
}


public function getNombreClientsNotifApresDemain()
{
    // Vérification de l'authentification et récupération de la configuration
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $config = ConfigurationRelanceAuto::where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
    } else {
        return 0; 
    }

  //  dd($config->nombre_jour_avant);
    // Vérification de la configuration
    if ($config && $config->envoyer_rappel_avant == 1) {
        $dateCible = Carbon::now()->addDays($config->nombre_jour_avant)->toDateString();

        $echeances = Echeance::whereDate('date_pay_echeance', $dateCible)
            ->whereNotNull('facture_id')
            ->whereHas('facture', function ($query) {
                $query->whereNotNull('client_id'); 
            })
            ->get();

        $nombreClients = $echeances->count();
        $details = [];

        foreach ($echeances as $echeance) {
            // Conversion explicite de date_pay_echeance en Carbon
            $datePayEcheance = Carbon::parse($echeance->date_pay_echeance);

            $details[] = [
                'id' => $echeance->id,
                'produit/service' => $echeance->facture->articles->map(function ($articleLivraison) {
                    return [
                        'id' => $articleLivraison->article->id,
                        'nom' => $articleLivraison->article->nom_article,
                        'quantite' => $articleLivraison->quantite_article,
                        'prix' => $articleLivraison->prix_total_tva_article,
                    ];
                }),
                'montant' => $echeance->montant_echeance,
                'client' => $echeance->facture->client->prenom_client . ' ' . $echeance->facture->client->nom_client,
                'date_prevue' => $datePayEcheance->toDateString(),
                'date_relance' => $datePayEcheance->subDays($config->nombre_jour_avant)->toDateString(),
            ];
        }

        return response()->json([
            'nombre_clients' => $nombreClients,
            'details' => $details,
        ]);
    }

    // Si la configuration n'est pas active, retourner 0
    return 0;
}


public function getNombreClientsNotifDans7Jours()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; 

        $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $config = ConfigurationRelanceAuto::where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

    if ($config && $config->envoyer_rappel_avant == 1) {
        $dateCible = Carbon::now()->addDays( 7)->toDateString();

        $echeances = Echeance::whereDate('date_pay_echeance', $dateCible)
            ->whereNotNull('facture_id') 
            ->whereHas('facture', function ($query) {
                $query->whereNotNull('client_id');
            })
            ->get();

            $nombreClients = $echeances->count();
            $details = [];
    
            foreach ($echeances as $echeance) {
                // Conversion explicite de date_pay_echeance en Carbon
                $datePayEcheance = Carbon::parse($echeance->date_pay_echeance);
    
                $details[] = [
                    'id' => $echeance->id,
                    'produit/service' => $echeance->facture->articles->map(function ($articleLivraison) {
                        return [
                            'id' => $articleLivraison->article->id,
                            'nom' => $articleLivraison->article->nom_article,
                            'quantite' => $articleLivraison->quantite_article,
                            'prix' => $articleLivraison->prix_total_tva_article,
                        ];
                    }),
                    'montant' => $echeance->montant_echeance,
                    'client' => $echeance->facture->client->prenom_client . ' ' . $echeance->facture->client->nom_client,
                    'date_prevue' => $datePayEcheance->toDateString(),
                    'date_relance' => $datePayEcheance->subDays($config->nombre_jour_avant)->toDateString(),
                ];
            }
    
            return response()->json([
                'nombre_clients' => $nombreClients,
                'details' => $details,
            ]);
        }
    return 0;
}

public function getNombreClientsNotifApresEcheance()
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $config = ConfigurationRelanceAuto::where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
    }
 if ($config && $config->envoyer_rappel_apres == 1) {
    $dateCible = Carbon::now()->addDays($config->nombre_jour_apres)->toDateString();

    $echeances = Echeance::whereDate('date_pay_echeance', $dateCible)
        ->whereNotNull('facture_id') 
          ->whereHas('facture', function ($query) {
                $query->whereNotNull('client_id'); // S'assurer que la facture est liée à un client
            })
        ->get();

        $nombreClients = $echeances->count();
        $details = [];

        foreach ($echeances as $echeance) {
            // Conversion explicite de date_pay_echeance en Carbon
            $datePayEcheance = Carbon::parse($echeance->date_pay_echeance);

            $details[] = [
                'id' => $echeance->id,
                'produit/service' => $echeance->facture->articles->map(function ($articleLivraison) {
                    return [
                        'id' => $articleLivraison->article->id,
                        'nom' => $articleLivraison->article->nom_article,
                        'quantite' => $articleLivraison->quantite_article,
                        'prix' => $articleLivraison->prix_total_tva_article,
                    ];
                }),
                'montant' => $echeance->montant_echeance,
                'client' => $echeance->facture->client->prenom_client . ' ' . $echeance->facture->client->nom_client,
                'date_prevue' => $datePayEcheance->toDateString(),
                'date_relance' => $datePayEcheance->addDays($config->nombre_jour_avant)->toDateString(),
            ];
        }

        return response()->json([
            'nombre_clients' => $nombreClients,
            'details' => $details,
        ]);
    }
// Si la configuration n'est pas active, retourner 0
return 0;
}

public function getNombreClientNotifApresEcheanceDans7Jours()
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $config = ConfigurationRelanceAuto::where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    if ($config && $config->envoyer_rappel_apres == 1) {
        $dateCible = Carbon::now()->subDays(7 - $config->nombre_jour_apres)->toDateString();

        $echeances = Echeance::whereDate('date_pay_echeance', $dateCible)
            ->whereNotNull('facture_id') 
            ->whereHas('facture', function ($query) {
                $query->whereNotNull('client_id'); // S'assurer que la facture est liée à un client
            })
            ->get();

            $nombreClients = $echeances->count();
            $details = [];
    
            foreach ($echeances as $echeance) {
                // Conversion explicite de date_pay_echeance en Carbon
                $datePayEcheance = Carbon::parse($echeance->date_pay_echeance);
    
                $details[] = [
                    'id' => $echeance->id,
                    'produit/service' => $echeance->facture->articles->map(function ($articleLivraison) {
                        return [
                            'id' => $articleLivraison->article->id,
                            'nom' => $articleLivraison->article->nom_article,
                            'quantite' => $articleLivraison->quantite_article,
                            'prix' => $articleLivraison->prix_total_tva_article,
                        ];
                    }),
                    'montant' => $echeance->montant_echeance,
                    'client' => $echeance->facture->client->prenom_client . ' ' . $echeance->facture->client->nom_client,
                    'date_prevue' => $datePayEcheance->toDateString(),
                    'date_relance' => $datePayEcheance->addDays($config->nombre_jour_avant)->toDateString(),
                ];
            }
    
            return response()->json([
                'nombre_clients' => $nombreClients,
                'details' => $details,
            ]);
        }
    return 0;
}
}