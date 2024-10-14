<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tva;
use App\Models\Depense;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\JournalVente;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CompteComptable;
use App\Models\facture_Etiquette;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class DepenseController extends Controller
{
    public function creerDepense(Request $request)
    {
        // Valider les données entrantes
        $validator = Validator::make($request->all(), [
            'num_depense' => 'nullable|string',
            'id_categorie_depense' => 'nullable|exists:categorie_depenses,id',
            'commentaire' => 'nullable|string',
            'date_paiement' => 'nullable|date',
            'tva_depense' => 'nullable|integer',
            'montant_depense_ht' => 'nullable|numeric',
            'montant_depense_ttc' => 'nullable|numeric',
            'plusieurs_paiement' => 'nullable|boolean',
            'duree_indeterminee' => 'nullable|boolean',
            'periode_echeance' =>' nullable|in:jour,mois,semaine',
            'nombre_periode' => 'nullable|integer',
    
            'echeances' => 'nullable|required_if:plusieurs_paiement,true|array',
            'echeances.*.date_pay_echeance' => 'nullable|date',
            'echeances.*.montant_echeance' => 'nullable|numeric|min:0',
    
            'doc_externe' => 'nullable|string|max:255',
            'num_facture' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'image_facture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'statut_depense' => 'required|in:payer,impayer',
            'id_paiement' => 'nullable|required_if:statut_depense,payer|exists:payements,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',

            'etiquettes' => 'nullable|array',
            'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $image_facture=null;
        if($request->hasFile('image_facture')){
            $image_facture=$request->file('image_facture')->store('images', 'public',);
             }
    
        // Déterminer l'utilisateur ou le sous-utilisateur connecté
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
    
        
        if ($request->plusieurs_paiement == true) {
            foreach ($request->echeances as $index => $echeanceData) {
        
                $depense = Depense::create([
                    'num_depense' => $request->num_depense. '-' .($index + 1),
                    'commentaire' => $request->input('commentaire'),
                    'date_paiement' => $echeanceData['date_pay_echeance'] ?? $request->date_paiement,
                    'montant_depense_ht' => $echeanceData['montant_echeance'] ?? $request->montant_depense_ht,
                    'montant_depense_ttc' => $echeanceData['montant_echeance'] ?? $request->montant_depense_ttc,
                    'plusieurs_paiement' => $request->plusieurs_paiement,
                    'duree_indeterminee' => $request->duree_indeterminee ?? false,
                    'periode_echeance' => $request->periode_echeance,
                    'nombre_periode' => $request->nombre_periode,
                    'doc_externe' => $request->doc_externe,
                    'num_facture' => $request->num_facture,
                    'date_facture' => $request->date_facture,
                    'image_facture' => $image_facture,
                    'statut_depense' => $request->statut_depense ?? 'impayer',
                    'fournisseur_id' => $request->fournisseur_id,
                    'id_categorie_depense' => $request->id_categorie_depense,
                    'sousUtilisateur_id' => $sousUtilisateurId,
                    'user_id' => $userId,
                ]);
                NumeroGeneratorService::incrementerCompteur($userId, 'depense');

            }
        } else {
            $numero = NumeroGeneratorService::genererNumero($userId, 'depense');
    
            $depense = Depense::create([
                'num_depense' =>  $request->num_depense ?? $numero,
                'id_categorie_depense' => $request->id_categorie_depense,
                'commentaire' => $request->input('commentaire'),
                'date_paiement' => $request->date_paiement,
                'tva_depense' => $request->tva_depense,
                'montant_depense_ht' => $request->montant_depense_ht,
                'montant_depense_ttc' => $request->montant_depense_ttc,
                'plusieurs_paiement' => $request->plusieurs_paiement ?? false,
                'duree_indeterminee' => $request->duree_indeterminee ?? false,
                'periode_echeance' => $request->periode_echeance ?? null,
                'nombre_periode' => $request->nombre_periode ?? null,
                'doc_externe' => $request->doc_externe,
                'num_facture' => $request->num_facture,
                'date_facture' => $request->date_facture,
                'image_facture' => $image_facture,
                'statut_depense' => $request->statut_depense ?? 'impayer',
                'id_paiement' => $request->id_paiement,
                'fournisseur_id' => $request->fournisseur_id,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);

            NumeroGeneratorService::incrementerCompteur($userId, 'depense');
            Artisan::call(command: 'optimize:clear');

        }
        Tva::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'tva_recolte' => 0, 
            'tva_deductif'=> $depense->montant_depense_ttc - $depense->montant_depense_ht,
            'tva_reverse'=> 0
        ]);

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des depenses ont été crées',
            'id_depense' => $depense->id
        ]);

        if ($request->has('etiquettes')) {

            foreach ($request->etiquettes as $etiquette) {
               $id_etiquette = $etiquette['id_etiquette'];
    
               facture_Etiquette::create([
                   'depense_id' => $depense->id,
                   'etiquette_id' => $id_etiquette
               ]);
            }
        }
        if ($depense->statut_depense == 'impayer') {
            Depense::envoyerNotificationSiImpayer($depense);
        }

        $compteVentesMarchandises = CompteComptable::where('nom_compte_comptable', 'Ventes de marchandises')->first();
        $compteFournisseurs = CompteComptable::where('nom_compte_comptable', 'Fournisseurs divers')->first();
        $compteTVADeductible = CompteComptable::where('nom_compte_comptable', 'TVA déductible')->first();


        if ($depense->tva_depense > 0 && $compteTVADeductible) {
            JournalVente::create([
                'id_depense' => $depense->id,
                'id_compte_comptable' => $compteTVADeductible->id,
                'debit' => 0,
                'credit' => $depense->montant_depense_ttc - $depense->montant_depense_ht,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
        if ( $compteVentesMarchandises) {
            JournalVente::create([
                'id_depense' => $depense->id,
                'id_compte_comptable' => $compteVentesMarchandises->id,
                'debit' => 0,
                'credit' => $depense->montant_depense_ht,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
        
        if ($depense->fournisseur_id && $compteFournisseurs) {
            JournalVente::create([
                'id_depense' => $depense->id,
                'id_compte_comptable' => $compteFournisseurs->id,
                'debit' => $depense->montant_depense_ttc,
                'credit' => 0,
                'sousUtilisateur_id' => $sousUtilisateurId,
                'user_id' => $userId,
            ]);
        }
    
        return response()->json(['message' => 'Dépense créée avec succès', 'depense' => $depense], 201);
    }
    
    public function listerDepenses()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            $sousUtilisateurId = $sousUtilisateur->id;
            $userId = $sousUtilisateur->id_user; 
    
            $depenses = Cache::remember('depenses', 3600, function () use ($sousUtilisateurId, $userId) {
            return Depense::with(['categorieDepense', 'fournisseur', 'Etiquetttes.etiquette','CommandeAchat'])
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->get();
            });
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $depenses = Cache::remember('depenses', 3600, function () use ($userId) {
               
           return Depense::with(['categorieDepense', 'fournisseur', 'Etiquetttes.etiquette','CommandeAchat'])
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function ($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
            });
        } else {
            return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
        }
        
        // $image_facture = $depenses->image_facture ? asset('storage/' . $depenses->image_facture) : null;


        $depensesArray = $depenses->map(function ($depense) {
            return [
                'num_depense' => $depense->num_depense,
                'id' => $depense->id,
                'commentaire' => $depense->commentaire,
                'date_paiement' => $depense->date_paiement,
                'date_creation' => $depense->created_at,
                'tva_depense' => $depense->tva_depense,
                'montant_depense_ht' => $depense->montant_depense_ht,
                'montant_depense_ttc' => $depense->montant_depense_ttc,
                'id_user' => $depense->user_id,
                'id_sous_utilisateur' => $depense->sousUtilisateur_id,
                'plusieurs_paiement' => $depense->plusieurs_paiement,
                'duree_indeterminee' => $depense->duree_indeterminee,
                'periode_echeance' => $depense->periode_echeance,
                'nombre_periode' => $depense->nombre_periode,
                'doc_externe' => $depense->doc_externe,
                'num_facture' => $depense->num_facture,
                'date_facture' => $depense->date_facture,
                'image_facture' => $depense->image_facture = Storage::url($depense->image_facture) ?: null,
                'statut_depense' => $depense->statut_depense,
                'id_paiement' => $depense->id_paiement,
                'id_commande_achat' => $depense->CommandeAchat->id ?? null,
                'num_commande_achat' => $depense->CommandeAchat->num_commandeAchat ?? null,
                'fournisseur_id' => $depense->fournisseur_id,
                'categorie_depense_id' => $depense->id_categorie_depense,
                'nom_categorie_depense' => optional($depense->categorieDepense)->nom_categorie_depense,
                'nom_fournisseur' => optional($depense->fournisseur)->nom,
                'prenom_fournisseur' => optional($depense->fournisseur)->prenom,
                'etiquettes' => $depense->Etiquetttes->map(function ($etiquette) {
                    return [
                        'id' => optional($etiquette->etiquette)->id,
                        'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
                    ];
                })->filter(function ($etiquette) {
                    return !is_null($etiquette['id']);
                })->values()->all(),
            ];
        });
    
        return response()->json(['depenses' => $depensesArray], 200);
    }
    
    

    
    public function modifierDepense(Request $request, $id)
    {
        // Valider les données entrantes
        $validator = Validator::make($request->all(), [
            'num_depense' => 'nullable|string|max:255',
            'id_categorie_depense' => 'required|exists:categorie_depenses,id',
            'commentaire' => 'nullable|string',
            'date_paiement' => 'nullable|date',
            'tva_depense' => 'nullable|integer',
            'montant_depense_ht' => 'nullable|numeric',
            'montant_depense_ttc' => 'nullable|numeric',
            'plusieurs_paiement' => 'nullable|boolean',
            'duree_indeterminee' => 'nullable|boolean',
            'periode_echeance' => 'required_if:duree_indeterminee,true|in:jour,mois,semaine',
            'nombre_periode' => 'required_if:duree_indeterminee,true|integer',
            'doc_externe' => 'nullable|string|max:255',
            'num_facture' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'image_facture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'statut_depense' => 'required|in:payer,impayer',
            'id_paiement' => 'nullable|required_if:statut_depense,payer|exists:payements,id',
            'fournisseur_id' => 'nullable|exists:fournisseurs,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $image_facture=null;
        if($request->hasFile('image_facture')){
            $image_facture=$request->file('image_facture')->store('images', 'public',);
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
    
        $depense = Depense::where('id', $id)
                    ->where(function ($query) use ($userId, $sousUtilisateurId) {
                        $query->where('user_id', $userId)
                              ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                    })
                    ->first();
    
        if (!$depense) {
            return response()->json(['error' => 'Dépense non trouvée'], 404);
        }

        $depense->image_facture = $image_facture;

        $depense->update($request->all());
        Artisan::call(command: 'optimize:clear');

        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'message' => 'Des depenses ont été Modifiées',
            'id_depense' => $depense->id
        ]);

        if ($request->has('etiquettes')) {
        facture_Etiquette::where('depense_id', $id)->delete();
    
        foreach ($request->etiquettes as $etiquette) {
            $id_etiquette = $etiquette['id_etiquette'];
    
            $depenseEtiquette= new facture_Etiquette([
            'depense_id' => $depense->id,
            'etiquette_id' => $id_etiquette
           ]);
           $depenseEtiquette->save();
         }
        }
        
        if ($depense->statut_depense == 'impayer') {
            Depense::envoyerNotificationSiImpayer($depense);
        }
    
        return response()->json(['message' => 'Dépense modifiée avec succès', 'depense' => $depense], 200);
    }
    
    public function PayerDepense($id){
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
    
        $depense = Depense::where('id', $id)
        ->where(function ($query) use ($userId, $sousUtilisateurId) {
            $query->where('user_id', $userId)
                  ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
        })
        ->first();

        if (!$depense) {
        return response()->json(['error' => 'Dépense non trouvée'], 404);
        }

        $depense->statut_depense = 'payer';
        $depense->save();
        return response()->json(['message' => 'Depense payée avec succès']);
    }

    public function supprimerDepense($id)
{
    // Déterminer l'utilisateur ou le sous-utilisateur connecté
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
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

    // Récupérer la dépense à supprimer
    $depense = Depense::where('id', $id)
                ->where(function ($query) use ($userId, $sousUtilisateurId) {
                    $query->where('user_id', $userId)
                          ->orWhere('sousUtilisateur_id', $sousUtilisateurId);
                })
                ->first();

    if (!$depense) {
        return response()->json(['error' => 'Dépense non trouvée'], 404);
    }

    // Supprimer la dépense
    $depense->delete();
    Artisan::call(command: 'optimize:clear');
    return response()->json(['message' => 'Dépense supprimée avec succès'], 200);
}

    
public function exporterDepenses()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'N° Facture');
    $sheet->setCellValue('C1', 'Date Facture');
    $sheet->setCellValue('D1', 'Date Paiement');
    $sheet->setCellValue('E1', 'Catégorie');
    $sheet->setCellValue('F1', 'Fournisseur');
    $sheet->setCellValue('G1', 'TVA (%)');
    $sheet->setCellValue('H1', 'Total HT');
    $sheet->setCellValue('I1', 'Total TTC');
    $sheet->setCellValue('J1', 'Statut Depense');
    $sheet->setCellValue('K1', 'Date Création');
    $sheet->setCellValue('L1', 'Date de modification');

    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $depenses = Depense::with(['fournisseur','categorieDepense'])
        ->where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $depenses = Depense::with(['fournisseur','categorieDepense'])
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
    foreach ($depenses as $depense) {
        $fournisseurNomComplet = $depense->fournisseur 
        ? $depense->fournisseur->prenom_fournisseur . ' - ' . $depense->fournisseur->nom_fournisseur
        : '';

        $sheet->setCellValue('A' . $row, $depense->num_depense);
        $sheet->setCellValue('B' . $row, $depense->num_facture);
        $sheet->setCellValue('C' . $row, $depense->date_facture);
        $sheet->setCellValue('D' . $row, $depense->date_paiement);
        $sheet->setCellValue('E' . $row, $depense->categorieDepense->nom_categorie_depense);
        $sheet->setCellValue('F' . $row, $fournisseurNomComplet);
        $sheet->setCellValue('G' . $row, $depense->tva_depense);
        $sheet->setCellValue('H' . $row, $depense->montant_depense_ht);
        $sheet->setCellValue('I' . $row, $depense->montant_depense_ttc);
        $sheet->setCellValue('J' . $row, $depense->statut_depense);
        $sheet->setCellValue('K' . $row, $depense->created_at);
        $sheet->setCellValue('L' . $row, $depense->updated_at);
   

        $row++;
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Depenses.xlsx';

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

public function RapportDepense(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date_debut' => 'required|date',
        'date_fin' => 'required|date|after_or_equal:date_debut',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => $validator->errors()], 400);
    }

    $dateDebut = $request->input('date_debut');
    $dateFin = $request->input('date_fin'). ' 23:59:59'; //Inclure la fin de la journée
    $userId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->id() : auth()->id();
    $parentUserId = auth()->guard('apisousUtilisateur')->check() ? auth('apisousUtilisateur')->user()->id_user : $userId;
    $depenses = Depense::with(['categorieDepense', 'fournisseur'])
    ->whereBetween('created_at', [$dateDebut, $dateFin])
    ->where(function ($query) use ($userId, $parentUserId) {
        $query->where('user_id', $userId)
            ->orWhere('user_id', $parentUserId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                $query->where('id_user', $parentUserId);
            });
    })
    ->get();

    return response()->json($depenses);
}


}
