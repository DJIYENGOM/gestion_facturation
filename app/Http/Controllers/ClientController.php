<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Facture;
use App\Models\Etiquette;
use Illuminate\Http\Request;
use Swift_TransportException;
use App\Exports\ClientsExport;
use App\Imports\ClientsImport;
use App\Models\CompteComptable;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Client_Etiquette;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Services\NumeroGeneratorService;
use App\Http\Requests\StoreClientRequest;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Mailer\Exception\TransportException;

class ClientController extends Controller
{
    public function ajouterClient(Request $request)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateur_id = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
        } elseif (auth()->check()) {
            $userId = auth()->id();
            $sousUtilisateur_id = null;
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
        $commonRules = [
            'type_client' => 'required|in:particulier,entreprise',
            'statut_client' => 'required|in:client,prospect',
            'categorie_id' => 'nullable|exists:categorie_clients,id',
            'num_client' => 'nullable|string|unique:clients,num_client',
            'etiquettes' => 'nullable|array',
            'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',


        ];
    
        $particulierRules = [
            'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
            'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        ];
    
        $entrepriseRules = [
            'num_id_fiscal' => 'required|string|max:255',
            'nom_entreprise' => 'required|string|max:50|min:2',

        ];
    
        $additionalRules = [
            'email_client' => 'required|email|max:255',
            'tel_client' => 'required|string|max:20|min:9',

        ];
    
        $rules = array_merge($commonRules, $additionalRules);
    
        if ($request->type_client == 'particulier') {
            $rules = array_merge($rules, $particulierRules);
        } elseif ($request->type_client == 'entreprise') {
            $rules = array_merge($rules, $entrepriseRules);
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        
        if (!$request->has('id_comptable')) {
                $compte = CompteComptable::where('nom_compte_comptable', 'Clients divers')
                                         ->first();
            if ($compte) {
                $id_comptable = $compte->id;
            } else {
                return response()->json(['error' => 'Compte comptable par défaut introuvable pour cet utilisateur'], 404);
            }
        } else {
            $id_comptable = $request->id_comptable;
        }
    
        $typeDocument = 'client';
        $numClient= NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $client = new Client([
            'num_client' => $numClient,
            'nom_client' => $request->nom_client,
            'prenom_client' => $request->prenom_client,
            'nom_entreprise' => $request->nom_entreprise,
            'adress_client' => $request->adress_client,
            'email_client' => $request->email_client,
            'tel_client' => $request->tel_client,
            'type_client' => $request->type_client,
            'statut_client' => $request->statut_client,
            'num_id_fiscal' => $request->num_id_fiscal,
            'code_postal_client' => $request->code_postal_client,
            'ville_client' => $request->ville_client,
            'pays_client' => $request->pays_client,
            'noteInterne_client' => $request->noteInterne_client,
            'nom_destinataire' => $request->nom_destinataire,
            'pays_livraison' => $request->pays_livraison,
            'ville_livraison' => $request->ville_livraison,
            'code_postal_livraison' => $request->code_postal_livraison,
            'tel_destinataire' => $request->tel_destinataire,
            'email_destinataire' => $request->email_destinataire,
            'infoSupplemnt' => $request->infoSupplemnt,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $userId,
            'categorie_id' => $request->categorie_id,
            'id_comptable' => $id_comptable,
        ]);
    
        $client->save();
        NumeroGeneratorService::incrementerCompteur($userId, 'client');

        Artisan::call('optimize:clear');

        if ($request->has('etiquettes')) {

        foreach ($request->etiquettes as $etiquette) {
           $id_etiquette = $etiquette['id_etiquette'];

           Client_Etiquette::create([
               'client_id' => $client->id,
               'etiquette_id' => $id_etiquette
           ]);
        }
    }

        return response()->json(['message' => 'Client ajouté avec succès', 'client' => $client]);
    }
    

    public function listerClients()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
        
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $clients = Client::with('categorie','CompteComptable', 'Etiquetttes.etiquette')
                ->where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();
    
            $clients = Client::with('categorie','CompteComptable', 'Etiquetttes.etiquette')
                ->where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    
            $clientsArray = Cache::remember('clientsArray', 3600, function () use ($clients) {
                return $clients->map(function ($client) {

            $clientArray = $client->toArray();
            $clientArray['nom_categorie'] = optional($client->categorie)->nom_categorie;
            $clientArray['nom_comptable'] = optional($client->CompteComptable)->nom_compte_comptable;

             // Formatter les étiquettes pour n'inclure que les attributs nécessaires
        $clientArray['etiquettes'] = $client->Etiquetttes->map(function ($etiquette) {
            return [
                'id' => optional($etiquette->etiquette)->id,
                'nom_etiquette' => optional($etiquette->etiquette)->nom_etiquette
            ];
        })->filter(function ($etiquette) {
            // Filtrer les étiquettes pour s'assurer qu'aucune entrée null ne soit incluse
            return !is_null($etiquette['id']);
        })->values()->all();

        // Supprimer les autres attributs non nécessaires si existants
        unset($clientArray['etiquetttes']); // Enlever si `etiquetttes` existe comme attribut principal
            return $clientArray;

        })->all();
        });
    
        return response()->json($clientsArray);
    }
    

public function modifierClient(Request $request, $id)
{
    $client = Client::findOrFail($id);

    // Déterminer l'utilisateur authentifié
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->fonction_admin) {
            return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
        }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        if ($client->sousUtilisateur_id !== $sousUtilisateurId) {
            return response()->json(['error' => 'Vous ne pouvez pas modifier ce client car il ne l\'a pas créé'], 401);
        }
    } elseif (auth()->check()) {
        $userId = auth()->id();
        if ($client->user_id !== $userId) {
            return response()->json(['error' => 'Vous ne pouvez pas modifier ce client car il ne l\'a pas créé'], 401);
        }
    } else {
        return response()->json(['error' => 'Non autorisé'], 401);
    }

    $commonRules = [
        'type_client' => 'required|in:particulier,entreprise',
        'statut_client' => 'required|in:client,prospect',
        'categorie_id' => 'nullable|exists:categorie_clients,id',

        'etiquettes' => 'nullable|array',
        'etiquettes.*.id_etiquette' => 'nullable|exists:etiquettes,id',
    ];

    $particulierRules = [
        'nom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
        'prenom_client' => ['required', 'string', 'min:2', 'regex:/^[a-zA-Zà_âçéèêëîïôûùüÿñæœÀÂÇÉÈÊËÎÏÔÛÙÜŸÑÆŒ\s\-]+$/'],
    ];

    $entrepriseRules = [
        'num_id_fiscal' => 'required|string|max:255',
        'nom_entreprise' => 'required|string|max:50|min:2',

    ];

    $additionalRules = [
        'email_client' => 'required|email|max:255',
        'tel_client' => 'required|string|max:20|min:9',

    ];

    $rules = array_merge($commonRules, $additionalRules);

    if ($request->type_client == 'particulier') {
        $rules = array_merge($rules, $particulierRules);
    } elseif ($request->type_client == 'entreprise') {
        $rules = array_merge($rules, $entrepriseRules);
    }

    // Valider les données reçues
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Mettre à jour les données du client
    $client->update($request->all());
    Artisan::call('optimize:clear');


    if ($request->has('etiquettes')) {
        Client_Etiquette::where('client_id', $id)->delete();

    foreach ($request->etiquettes as $etiquette) {
        $id_etiquette = $etiquette['id_etiquette'];

        $clientEtiquette= new Client_Etiquette([
        'client_id' => $client->id,
        'etiquette_id' => $id_etiquette
       ]);
       $clientEtiquette->save();
     }
    }

    return response()->json(['message' => 'Client modifié avec succès', 'client' => $client]);
}


public function supprimerClient($id)
{

    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin && !$sousUtilisateur->supprimer_donnees) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

       $client = Client::where('id',$id)
            ->where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->first();
            
            if($client){
                $client->delete();
                Artisan::call('optimize:clear');

            return response()->json(['message' => 'client supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Vous ne pouvez pas modifier ce client'], 401);
            }

    }elseif (auth()->check()) {
        $userId = auth()->id();

        $client = Client::where('id',$id)
            ->where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->first();
            if($client){
                $client->delete();
                Artisan::call('optimize:clear');

                return response()->json(['message' => 'client supprimé avec succès']);
            }else {
                return response()->json(['error' => 'Vous ne peuvez pas modifier ce client'], 401);
            }

    }else {
        return response()->json(['error' => 'Vous n\'etes pas connectéd'], 401);
    }

}

public function importClient(Request $request)
{
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur_id = auth('apisousUtilisateur')->id();
        $user_id = auth('apisousUtilisateur')->user()->id_user;
    } elseif (auth()->check()) {
        $user_id = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:xlsx,xls'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $compte = CompteComptable::where('nom_compte_comptable', 'Clients divers')->first();
    $id_comptable = $compte ? $compte->id : null;

    // Traitement du fichier avec capture des erreurs
    try {
        Excel::import(new ClientsImport($user_id, $sousUtilisateur_id, $id_comptable), $request->file('file'));
        
        return response()->json(['message' => 'Clients importés avec succès']);
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();

        foreach ($failures as $failure) {
            Log::error('Row ' . $failure->row() . ' has errors: ' . json_encode($failure->errors()));
        }

        return response()->json(['errors' => $failures], 422);
    }
}

public function exportClients()
{
    // Créer une nouvelle feuille de calcul
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Définir les en-têtes
    $sheet->setCellValue('A1', 'num_client');
    $sheet->setCellValue('B1', 'nom_client');
    $sheet->setCellValue('C1', 'prenom_client');
    $sheet->setCellValue('D1', 'nom_entreprise');
    $sheet->setCellValue('E1', 'email_client');
    $sheet->setCellValue('F1', 'adress_client');
    $sheet->setCellValue('G1', 'tel_client');
    $sheet->setCellValue('H1', 'num_id_fiscal');
    $sheet->setCellValue('I1', 'type_client');
    $sheet->setCellValue('J1', 'statut_client');
    $sheet->setCellValue('K1', 'code_postal_client');
    $sheet->setCellValue('L1', 'ville_client');
    $sheet->setCellValue('M1', 'pays_client');
    $sheet->setCellValue('N1', 'noteInterne_client');
    $sheet->setCellValue('O1', 'nom_destinataire');
    $sheet->setCellValue('P1', 'pays_livraison');
    $sheet->setCellValue('Q1', 'ville_livraison');
    $sheet->setCellValue('R1', 'code_postal_livraison');
    $sheet->setCellValue('S1', 'tel_destinataire');
    $sheet->setCellValue('T1', 'email_destinataire');

    // Récupérer les données des clients
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->export_excel && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }
        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent

        $clients = Client::where('sousUtilisateur_id', $sousUtilisateurId)
            ->orWhere('user_id', $userId)
            ->get();
    } elseif (auth()->check()) {
        $userId = auth()->id();

        $clients = Client::where('user_id', $userId)
            ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                $query->where('id_user', $userId);
            })
            ->get();
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Remplir les données
    $row = 2;
    foreach ($clients as $client) {
        $sheet->setCellValue('A' . $row, $client->num_client);
        $sheet->setCellValue('B' . $row, $client->nom_client);
        $sheet->setCellValue('C' . $row, $client->prenom_client);
        $sheet->setCellValue('D' . $row, $client->nom_entreprise);
        $sheet->setCellValue('E' . $row, $client->email_client);
        $sheet->setCellValue('F' . $row, $client->adress_client);
        $sheet->setCellValue('G' . $row, $client->tel_client);
        $sheet->setCellValue('H' . $row, $client->num_id_fiscal);
        $sheet->setCellValue('I' . $row, $client->type_client);
        $sheet->setCellValue('J' . $row, $client->statut_client);
        $sheet->setCellValue('K' . $row, $client->code_postal_client);
        $sheet->setCellValue('L' . $row, $client->ville_client);
        $sheet->setCellValue('M' . $row, $client->pays_client);
        $sheet->setCellValue('N' . $row, $client->noteInterne_client);
        $sheet->setCellValue('O' . $row, $client->nom_destinataire);
        $sheet->setCellValue('P' . $row, $client->pays_livraison);
        $sheet->setCellValue('Q' . $row, $client->ville_livraison);
        $sheet->setCellValue('R' . $row, $client->code_postal_livraison);
        $sheet->setCellValue('S' . $row, $client->tel_destinataire);
        $sheet->setCellValue('T' . $row, $client->email_destinataire);
   
        $row++;
    }

    // Effacer les tampons de sortie pour éviter les caractères indésirables
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Définir le nom du fichier
    $fileName = 'clients.xlsx';

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

public function sendClientEmail(Request $request, $id_facture)
{
    // Valider les informations nécessaires
    $validatedData = $request->validate([
        'adresse_destinataire' => 'required|email',
        'objet' => 'required|string|max:255',
        'message' => 'required|string',
    ]);

    // Récupérer la facture
    $invoice = Facture::findOrFail($id_facture);


    // Charger les articles et autres relations nécessaires pour le PDF
    $invoice->load(['articles', 'echeances']);

    // Générer le PDF
    $pdf = Pdf::loadView('invoices.template', compact('invoice'));
    $pdfPath = storage_path('app/public/invoices/') . 'invoice_' . $invoice->id . '.pdf';
    $pdf->save($pdfPath);

    // Vérifier que le fichier a été stocké avec succès
    if (!file_exists($pdfPath)) {
        return response()->json(['error' => 'Erreur lors de la génération du fichier PDF'], 500);
    }

    // Récupérer les informations supplémentaires
    $adresseDestinataire = $validatedData['adresse_destinataire'];
    $objet = $validatedData['objet'];
    $messageEmail = $validatedData['message'];

    try {
        // Envoyer l'email au destinataire avec le fichier PDF en pièce jointe
        Mail::send([], [], function ($message) use ($adresseDestinataire, $objet, $messageEmail, $pdfPath) {
            $message->from('diekasse22@gamil.com');
            $message->to($adresseDestinataire)
                ->subject($objet)
                ->html($messageEmail)
                ->attach($pdfPath, [
                    'as' => 'Facture.pdf',
                    'mime' => 'application/pdf',
                ]);
        });

        return response()->json(['success' => 'Email envoyé avec succès']);

    } catch (Swift_TransportException $e) {
        // Gestion des exceptions si l'envoi échoue
        return response()->json(['error' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()], 500);
    }
}

}