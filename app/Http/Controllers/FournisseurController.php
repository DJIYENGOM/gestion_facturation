<?php

namespace App\Http\Controllers;

use App\Models\Historique;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use App\Models\CompteComptable;
use App\Models\facture_Etiquette;
use App\Services\NumeroGeneratorService;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FournisseurController extends Controller
{

    public function ajouterFournisseur(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $user_id = auth('apisousUtilisateur')->user()->id_user;
        } elseif (auth()->check()) {
            $user_id = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
        }
    
        // Règles de validation communes
        $commonRules = [
            'type_fournisseur' => 'required|in:particulier,entreprise',
            'num_fournisseur' => 'nullable|string|unique:fournisseurs,num_fournisseur',
            'doc_fournisseur' => 'nullable|file|mimes:pdf,doc,docx,excel,xls,xlsx|max:10240',
            'etiquettes' => 'nullable|array',
            'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',
            'code_banque' => 'nullable|alpha_num|size:5|regex:/^SN\d{3}$/', // Valide avec "SN" suivi de 3 chiffres
            'code_guichet' => 'nullable|digits:5',
            'num_compte' => 'nullable|digits:12',
            'cle_rib' => 'nullable|digits:2',
            'iban' => 'nullable|alpha_num|size:34',
        ];
    
        // Règles spécifiques aux particuliers
        $particulierRules = [
            'nom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        ];
    
        // Règles spécifiques aux entreprises
        $entrepriseRules = [
            'num_id_fiscal' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:50|min:2',
        ];
    
        // Règles supplémentaires
        $additionalRules = [
            'email_fournisseur' => 'required|email|max:255',
            'tel_fournisseur' => 'required|string|max:20|min:9',
        ];
    
        // Fusion des règles selon le type de fournisseur
        $rules = array_merge($commonRules, $additionalRules);
        if ($request->type_fournisseur == 'particulier') {
            $rules = array_merge($rules, $particulierRules);
        } elseif ($request->type_fournisseur == 'entreprise') {
            $rules = array_merge($rules, $entrepriseRules);
        }
    
        // Validation des données
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Gestion de l'identifiant comptable par défaut
        if (!$request->has('id_comptable')) {
            $compte = CompteComptable::where('nom_compte_comptable', 'fournisseurs divers')->first();
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        // Génération du numéro de fournisseur
        $typeDocument = 'fournisseur';
        $numFournisseur = NumeroGeneratorService::genererNumero($user_id, $typeDocument);
    
        // Création du fournisseur
        $fournisseur = new Fournisseur([
            'num_fournisseur' => $request->num_fournisseur ?? $numFournisseur,
            'nom_fournisseur' => $request->nom_fournisseur,
            'prenom_fournisseur' => $request->prenom_fournisseur,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_fournisseur' => $request->adress_fournisseur,
            'email_fournisseur' => $request->email_fournisseur,
            'tel_fournisseur' => $request->tel_fournisseur,
            'type_fournisseur' => $request->type_fournisseur,
            'num_id_fiscal' => $request->num_id_fiscal,
            'code_postal_fournisseur' => $request->code_postal_fournisseur,
            'ville_fournisseur' => $request->ville_fournisseur,
            'pays_fournisseur' => $request->pays_fournisseur,
            'noteInterne_fournisseur' => $request->noteInterne_fournisseur,
            'doc_fournisseur' => $request->doc_fournisseur,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
            'categorie_id' => $request->categorie_id,
            'id_comptable' => $id_comptable,
            'code_banque' => $request->code_banque,
            'code_guichet' => $request->code_guichet,
            'num_compte' => $request->num_compte,
            'cle_rib' => $request->cle_rib,
            'iban' => $request->iban
        ]);
    
        $fournisseur->save();
        NumeroGeneratorService::incrementerCompteur($user_id, 'fournisseur');
    
        // Enregistrement dans l'historique
        Historique::create([
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $user_id,
            'message' => 'Des Fournisseurs ont été Ajoutés',
            'id_fournisseur' => $fournisseur->id
        ]);
    
        // Association des étiquettes
        if ($request->has('etiquettes')) {
            foreach ($request->etiquettes as $etiquette) {
                $id_etiquette = $etiquette['id_etiquette'];
                Facture_Etiquette::create([
                    'fournisseur_id' => $fournisseur->id,
                    'etiquette_id' => $id_etiquette
                ]);
            }
        }
    
        return response()->json(['message' => 'Fournisseur ajouté avec succès', 'fournisseur' => $fournisseur]);
    }
    

    public function listerTousFournisseurs()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
              return response()->json(['error' => 'Accès non autorisé'], 403);
              }
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $fournisseurs = Fournisseur::with('Etiquettes.etiquette')
             ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $fournisseurs = Fournisseur::with('Etiquettes.etiquette')
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        
        $fournisseursArray = $fournisseurs->map(function ($fournisseur) {
            $fournisseurArray = $fournisseur->toArray();
        
            // Vérification des étiquettes
            $fournisseurArray['etiquettes'] = ($fournisseur->Etiquettes ?? collect())->map(function ($etiquette) {
                return [
                    'id' => optional($etiquette->etiquette)->id,
                    'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette,
                ];
            })->filter(function ($etiquette) {
                return !is_null($etiquette['id']);
            })->values()->all();
        
            return $fournisseurArray;
        });

    
        return response()->json($fournisseursArray);
    }

    public function modifierFournisseur(Request $request, $id)
{
    $fournisseur = Fournisseur::findOrFail($id);

    // Déterminer l'utilisateur authentifié
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user;
        if ($fournisseur->sousUtilisateur_id !== $sousUtilisateurId) {
            return response()->json(['error' => 'Cette sous-utilisateur ne peut pas modifier ce fournisseur car il ne l\'a pas créé'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId=null;
        if ($fournisseur->user_id !== $userId) {
            return response()->json(['error' => 'Cet utilisateur ne peut pas modifier ce fournisseur car il ne l\'a pas créé'], 401);
        }
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $commonRules = [
        'type_fournisseur' => 'required|in:particulier,entreprise',
        'num_fournisseur' => 'nullable|string|unique:fournisseurs,num_fournisseur',
        'doc_fournisseur' => 'nullable|file|mimes:pdf,doc,docx,excel,xls,xlsx|max:10240',

        'etiquettes' => 'nullable|array',
        'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',

        'code_banque' => 'nullable|alpha_num|size:5|regex:/^SN\d{3}$/', // Valide avec "SN" suivi de 3 chiffres
        'code_guichet' => 'nullable|digits:5',
        'num_compte' => 'nullable|digits:12',
        'cle_rib' => 'nullable|digits:2',
        'iban' => 'nullable|alpha_num|size:34',

    ];

    $particulierRules = [
        'nom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'prenom_fournisseur' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    ];

    $entrepriseRules = [
        'num_id_fiscal' => 'required|string|max:255',
        'nom_entreprise' => 'required|string|max:50|min:2',

    ];

    $additionalRules = [
        'email_fournisseur' => 'required|email|max:255',
        'tel_fournisseur' => 'required|string|max:20|min:9',

    ];

    $rules = array_merge($commonRules, $additionalRules);

    if ($request->type_fournisseur == 'particulier') {
        $rules = array_merge($rules, $particulierRules);
    } elseif ($request->type_fournisseur == 'entreprise') {
        $rules = array_merge($rules, $entrepriseRules);
    }
    // Valider les données reçues
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Mettre à jour les données du fournisseur
    $fournisseur->update($request->all());

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Fournisseurs ont été Modifiés',
        'id_fournisseur' => $fournisseur->id
    ]);

    if ($request->has('etiquettes')) {
        facture_Etiquette::where('fournisseur_id', $id)->delete();

    foreach ($request->etiquettes as $etiquette) {
        $id_etiquette = $etiquette['id_etiquette'];

        $factureEtiquette= new facture_Etiquette([
        'fournisseur_id' => $fournisseur->id,
        'etiquette_id' => $id_etiquette
       ]);
       $factureEtiquette->save();
     }
    }
    return response()->json(['message' => 'fournisseur modifié avec succès', 'fournisseur' => $fournisseur]);
}

public function supprimerFournisseur($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $fournisseur = Fournisseur::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($fournisseur){
                $fournisseur->delete();
            return response()->json(['message' => 'fournisseur supprimé avec succès']);
            }else {
                return response()->json(['error' => 'ce sous utilisateur ne peut pas supprimer ce fournisseur'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $fournisseur = Fournisseur::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            if($fournisseur){
                $fournisseur->delete();
                return response()->json(['message' => 'fournisseur supprimé avec succès']);
            }else {
                return response()->json(['error' => 'cet utilisateur ne peut pas supprimer ce fournisseur'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

}

public function exporterFournisseurs()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'Numéro');
    $sheet->setCellValue('B1', 'Prenom Fournisseur');
    $sheet->setCellValue('C1', 'Nom Fournisseur');
    $sheet->setCellValue('D1', 'Email Fournisseur');
    $sheet->setCellValue('E1', 'Adress Fournisseur');
    $sheet->setCellValue('F1', 'Tel Fournisseur');
    $sheet->setCellValue('G1', 'Entreprise Fournisseur)');
    $sheet->setCellValue('H1', 'N° Fiscal');
    $sheet->setCellValue('I1', 'Pays Fournisseur');
    $sheet->setCellValue('J1', 'Ville Fournisseur');
    $sheet->setCellValue('K1', 'Note Interne');
    $sheet->setCellValue('L1', 'Code Postal');
    $sheet->setCellValue('M1', 'Code Banque');
    $sheet->setCellValue('N1', 'code_guichet');
    $sheet->setCellValue('O1', 'num_compte');
    $sheet->setCellValue('P1', 'IBAN');
    $sheet->setCellValue('Q1', 'cle_rib');
   

    // Récupérer les données des articles
    if (auth()->guard('apisousUtilisateur')->check()) {

        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $Fournisseurs = Fournisseur::where(function ($query) use ($sousUtilisateurId, $userId) {
            $query->where('sousUtilisateur_id', $sousUtilisateurId)
                  ->orWhere('user_id', $userId);
        })
        ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $Fournisseurs = Fournisseur::where(function ($query) use ($userId) {
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
    foreach ($Fournisseurs as $Fournisseur) {
       

        $sheet->setCellValue('A' . $row, $Fournisseur->num_Fournisseur);
        $sheet->setCellValue('B' . $row, $Fournisseur->prenom_Fournisseur);
        $sheet->setCellValue('C' . $row, $Fournisseur->nom_Fournisseur);
        $sheet->setCellValue('D' . $row, $Fournisseur->email_Fournisseur);
        $sheet->setCellValue('E' . $row, $Fournisseur->adress_Fournisseur);
        $sheet->setCellValue('F' . $row, $Fournisseur->tel_Fournisseur);
        $sheet->setCellValue('G' . $row, $Fournisseur->nom_entreprise);
        $sheet->setCellValue('H' . $row, $Fournisseur->num_id_fiscal);
        $sheet->setCellValue('I' . $row, $Fournisseur->pays_fournisseur);
        $sheet->setCellValue('J' . $row, $Fournisseur->ville_fournisseur);
        $sheet->setCellValue('K' . $row, $Fournisseur->noteInterne_fournisseur);
        $sheet->setCellValue('L' . $row, $Fournisseur->code_postal_fournisseur);
        $sheet->setCellValue('M' . $row, $Fournisseur->code_banque);
        $sheet->setCellValue('N' . $row, $Fournisseur->code_guichet);
        $sheet->setCellValue('O' . $row, $Fournisseur->num_compte);
        $sheet->setCellValue('P' . $row, $Fournisseur->iban);
        $sheet->setCellValue('Q' . $row, $Fournisseur->cle_rib);
   

        $row++;
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'Fournisseurs.xlsx';

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
}
