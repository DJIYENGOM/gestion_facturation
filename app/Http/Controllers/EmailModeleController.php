<?php
namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\EmailModele;
use Illuminate\Http\Request;
use App\Models\VariableEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class EmailModeleController extends Controller
{
    
    public function createEmailModele(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'type_modele' => 'required|in:facture,devi,resumer_vente,recu_paiement,relanceAvant_echeance,relanceApres_echeance,commande_vente,livraison,fournisseur',
            'object' => 'required|string|max:255',
            'contenu' => 'required|string',
            'fichiers.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:2048', // Gérer les fichiers multiples
            'user_id' => 'nullable|exists:users,id',
            'sousUtilisateur_id' => 'nullable|exists:sous__utilisateurs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $emailModele = EmailModele::create([
            'type_modele' => $request->type_modele,
            'object' => $request->object,
            'contenu' => $request->contenu,
            'user_id' => $request->user_id,
            'sousUtilisateur_id' => $request->sousUtilisateur_id,
        ]);

       

        // Gérer les fichiers joints
        if ($request->hasFile('fichiers')) {
            foreach ($request->file('fichiers') as $file) {
                // Enregistrer le fichier
                $path = $file->store('email_attachments', 'public');

                // Créer l'entrée de fichier joint
                $emailModele->attachments()->create([
                    'chemin_fichier' => $path,
                ]);
            }
        }

        // Retourner la réponse
        return response()->json([
            'message' => 'Modèle d\'email créé avec succès.',
            'emailModele' => $emailModele,
        ], 201);
    }


    public function DetailEmail_genererPDF($id_facture)
    {
        $invoice = Facture::findOrFail($id_facture);
    
        // Charger les articles et autres relations nécessaires
        $invoice->load(['articles', 'echeances']);
    
        // Chemin du répertoire de stockage des factures
        $directoryPath = storage_path('app/public/invoices/');
    
        // Vérifier si le répertoire existe, sinon le créer
        if (!file_exists($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }
    
        // Génération du PDF
        $pdf = Pdf::loadView('invoices.template', compact('invoice'));
        $pdfPath = $directoryPath . 'invoice_' . $invoice->id . '.pdf';
    
        // Enregistrer le PDF
        $pdf->save($pdfPath);
    
        // Retourner le chemin du PDF généré
        return response()->json(['pdfPath' => $pdfPath, 'invoiceId' => $invoice->id]);
    }
}
