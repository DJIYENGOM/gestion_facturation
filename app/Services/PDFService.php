<?php

namespace App\Services;

use App\Models\Devi;
use Illuminate\Support\Facades\Storage;

 use Dompdf\Dompdf;
 use Dompdf\Options;
 use App\Models\Facture;
 use App\Models\ModelDocument;
 
 class PDFService
 {
    public function genererPDFFacture($factureId, $modelDocumentId)
    {
        $facture = Facture::with(['user', 'client', 'articles.article', 'echeances'])->find($factureId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$facture || !$modelDocument) {
        return response()->json(['error' => 'Facture ou modèle introuvable'], 404);
    }
    
    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('[numero]', $facture->num_facture, $content);
    $content = str_replace('[expediteur_nom]', $facture->user->name, $content);
    $content = str_replace('[expediteur_email]', $facture->user->email, $content);
    $content = str_replace('[expediteur_tel]', $facture->user->tel_entreprise ?? 'N/A', $content);

    // 1. Créer le chemin complet vers l'image
    $logoPath = storage_path('app/public/' . $facture->user->logo);
    // 2. Lire le contenu de l'image et l'encoder en base64
    if(file_exists($logoPath)) {
    
    $logoData = base64_encode(file_get_contents($logoPath));
    // 3. Déterminer le type MIME en fonction de l'extension
    $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
    $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';
     // 4. Remplacer le mot-clé "logo" dans le HTML par l'image encodée en base64
    //    pour afficher le logo sans déclencher d'erreurs CORS
    $content = str_replace('[logo]', "data:$mimeType;base64,$logoData", $content);
    }

    $content = str_replace('[destinataire_nom]', $facture->client->prenom_client . ' ' . $facture->client->nom_client, $content);
    $content = str_replace('[destinataire_email]', $facture->client->email_client, $content);
    $content = str_replace('[destinataire_tel]', $facture->client->tel_client, $content);
    $content = str_replace('[date_facture]', \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y'), $content);

    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($facture->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->TVA_article, 2) . " </td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " </td>
            <td>" . number_format($article->prix_total_article, 2) . " </td>
        </tr>";
    }
    $content = str_replace('articles', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('[montant_total_ttc]', number_format($facture->prix_TTC, 2) . " fcfa", $content);
    $content = str_replace('[montant_total_ht]', number_format($facture->prix_HT, 2) . " fcfa", $content);

    $montant_tva = $facture->prix_TTC - $facture->prix_HT;
    $content = str_replace('[montant_total_tva]', number_format($montant_tva, 2) . " fcfa", $content);

    // Gérer les échéances
    if ($facture->type_paiement == 'echeance') {
        $echeancesHtml = ' 
                <thead>
                    <tr>
                        <th>Prévue le</th>
                        <th>Montant</th>
                     </tr>
                </thead>';
        foreach ($facture->echeances as $echeance) {
            $echeancesHtml .= "
                <tbody>
                    <tr>
                        <td>" . \Carbon\Carbon::parse($echeance->date_pay_echeance)->format('d/m/Y') . "</td>
                        <td>" . number_format($echeance->montant_echeance, 2) . " fcfa</td>
                    </tr>
                </tbody>
           ";
        }
        $content = str_replace('[echeances]', $echeancesHtml, $content);
    } else {
        $content = str_replace('[echeances]', '', $content);
    }

      // Gérer les accompte
      if ($facture->type_paiement == 'facture_Accompt') {
        $accomptHtml = '
         <thead>
                <tr>
                    <th>Factures dacomptee</th>
                    <th>Montant</th>
                </tr>
        </thead>
        ';
        foreach ($facture->factureAccompts as $facture_Accompt) {
            $accomptHtml .= "
            <tbody>
                <tr>
                    <td>{$facture_Accompt->titreAccomp} Facture N {$facture->num_facture} du ( " . \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y') . ") </td>
                    <td>" . number_format($facture_Accompt->montant, 2) . " fcfa</td>
                </tr>  
            </tbody>
          ";
        }
        $content = str_replace('[accompte]', $accomptHtml, $content);
    } else {
        $content = str_replace('[accompte]', '', $content);
    }


    // Gérer les signatures
        // 1. Créer le chemin complet vers l'image
        if($modelDocument->signatureExpediteurModel){
        $logoPath = storage_path('app/public/' . $modelDocument->image_expediteur);

        // 2. Vérifier que l'image existe et renvoyer une erreur si elle est introuvable
        if (!file_exists($logoPath)) {
            return response()->json(['error' => 'L\'image de signature expéditeur est introuvable'], 404);
        }

        // 3. Lire le contenu de l'image et l'encoder en base64
        $logoData = base64_encode(file_get_contents($logoPath));

        // 4. Déterminer le type MIME en fonction de l'extension
        $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
        $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';

        // 5. Créer le HTML avec l'image encodée en base64 pour l'intégrer dans le contenu PDF
        if ($modelDocument->signatureExpediteurModel && $modelDocument->image_expediteur ) {
            $signatureExpediteurTitre="signature Expediteur";
            $mention_expediteur="Mention Expediteur";
            $signatureExpediteurHtml = "<img style='max-width: 100%;margin-top: 10px; height: auto;' src='data:$mimeType;base64,$logoData' alt='Signature Expéditeur' />";
            $content .= "<div style='margin-top: 20px;'>
                        <span>{$mention_expediteur}: </span>
                        <span>{$modelDocument->mention_expediteur}</span>
                        <p>{$signatureExpediteurTitre}</p>
                        <p>{$signatureExpediteurHtml}</p>
                </div>";
        }else {
            $content .= "";
        }
    }

        if ($modelDocument->signatureDestinataireModel) {
            $mention_destinataire="Mention Destinataire";
            $content .= "<div style='margin-top: 20px; text-align: right;'>
            <span>{$mention_destinataire}: </span>
            <span>{$modelDocument->mention_destinataire}</span>
          </div>";
        } else {
            $content .= "";
        }

    // Gérer autre image
    if($modelDocument->autresImagesModel ){
     // 1. Créer le chemin complet vers l'image
     $autreImagePath = storage_path('app/public/' . $modelDocument->image);

     // 2. Vérifier que l'image existe et renvoyer une erreur si elle est introuvable
     if (!file_exists($autreImagePath)) {
         return response()->json(['error' => 'L\'image de signature expéditeur est introuvable'], 404);
     }

     // 3. Lire le contenu de l'image et l'encoder en base64
     $logoData = base64_encode(file_get_contents($autreImagePath));

     // 4. Déterminer le type MIME en fonction de l'extension
     $extension = pathinfo($autreImagePath, PATHINFO_EXTENSION);
     $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';

      // 5. Créer le HTML avec l'image encodée en base64 pour l'intégrer dans le contenu PDF
      if ($modelDocument->autresImagesModel && $modelDocument->image ) {
        $AutreImageTitre="Autre Image";
        $AutreImageTitreHtml = "<img style='max-width: 100%;margin-top: 10px; height: auto;' src='data:$mimeType;base64,$logoData' alt='Signature Expéditeur' />";
        $content .= "<div style='margin-top: 20px;'>
                    <p>{$AutreImageTitre}</p>
                    <p>{$AutreImageTitreHtml}</p>
            </div>";
    }else {
        $content .= "";
    }
    }
    // Gérer les conditions de paiement
        if ($modelDocument->conditionsPaiementModel) {
            $content .= "
            <div style='margin-top: 20px; text-align: right;'>
            <h4>Conditions de paiement</h4>
            <span>{$modelDocument->conditionPaiement}</span>
          </div>";
        } else {
            $content .= "";
        }               


    // Gérer les coordonnées bancaires
        if ($modelDocument->coordonneesBancairesModel) {
            $coordonneesBancairesHtml = "<h4>Coordonnées bancaires</h4>
                <p>Titulaire du compte : {$modelDocument->titulaire_compte}</p>
                <p>IBAN : {$modelDocument->IBAN}</p>
                <p>BIC : {$modelDocument->BIC}</p>";
                $content .= "<div style='margin-top: 20px;'>
                <span>{$coordonneesBancairesHtml}</span>
              </div>";
        } else {
            $content .= "";
        }

    // Gérer la note de pied de page
        if ($modelDocument->notePiedPageModel) {
            $content .= "<div style='position: fixed; bottom: 0; left: 0; width: 100%; margin: 0;  border-top: 1px solid #eee;'>
           <span>{$modelDocument->peidPage}</span>
        </div>";
        } else {
            $content .= "";
        }

    // 3. Appliquer le CSS du modèle en ajoutant une structure HTML complète
    $css = $modelDocument->css;
    $content = "<!doctype html>
    <html lang='fr'>
    <head>
        <meta charset='utf-8'>
        <style>{$css}</style>
    </head>
    <body>
        {$content}
    </body>
    </html>";

    // 4. Configurer DOMPDF avec des options avancées et générer le PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isCssFloatEnabled', true);
    
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');

    $dompdf->render();
 

        $pdfDirectory = storage_path('app/public/factures');
        $pdfPath = $pdfDirectory . '/facture_' . $facture->num_facture . '.pdf';

        // Vérifiez et créez le dossier si nécessaire
        if (!file_exists($pdfDirectory)) {
            mkdir($pdfDirectory, 0777, true); // true pour créer les sous-dossiers nécessaires
        }

file_put_contents($pdfPath, $dompdf->output());

return $pdfPath;
    
}

public function genererPDFDevis($deviId, $modelDocumentId)
{
    // 1. Récupérer le devis et le modèle de document depuis la base de données
    $devi = Devi::with(['user', 'client', 'articles.article', 'echeances'])->find($deviId);
    $modelDocument = ModelDocument::where('id', $modelDocumentId)->first();

    if (!$devi || !$modelDocument) {
        return response()->json(['error' => 'Devis ou modèle introuvable'], 404);
    }
    
    // 2. Remplacer les variables dynamiques par les données réelles
    $content = $modelDocument->content;
    $content = str_replace('[numero]', $devi->num_devi, $content);
    $content = str_replace('[expediteur_nom]', $devi->user->name, $content);
    $content = str_replace('[expediteur_email]', $devi->user->email, $content);
    $content = str_replace('[expediteur_tel]', $devi->user->tel_entreprise ?? 'N/A', $content);

    // Ajouter le logo en base64 pour éviter les problèmes CORS
    $logoPath = storage_path('app/public/' . $devi->user->logo);
    if (file_exists($logoPath)) {
        $logoData = base64_encode(file_get_contents($logoPath));
        $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
        $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';
        $content = str_replace('[logo]', "data:$mimeType;base64,$logoData", $content);
    } else {
        $content = str_replace('[logo]', '', $content);
    }

    $content = str_replace('[destinataire_nom]', $devi->client->prenom_client . ' ' . $devi->client->nom_client, $content);
    $content = str_replace('[destinataire_email]', $devi->client->email_client, $content);
    $content = str_replace('[destinataire_tel]', $devi->client->tel_client, $content);
    $content = str_replace('[date_devi]', \Carbon\Carbon::parse($devi->created_at)->format('d/m/Y'), $content);

    // Gérer la liste des articles
    $articlesHtml = '';
    foreach ($devi->articles as $article) {
        $articlesHtml .= "<tr>
            <td>{$article->article->nom_article}</td>
            <td>{$article->quantite_article}</td>
            <td>" . number_format($article->article->TVA_article, 2) . " </td>
            <td>" . number_format($article->article->prix_unitaire, 2) . " </td>
            <td>" . number_format($article->prix_total_article, 2) . " </td>
        </tr>";
    }
    $content = str_replace('articles', $articlesHtml, $content);

    // Gérer le montant total
    $content = str_replace('[montant_total_ttc]', number_format($devi->prix_TTC, 2) . " fcfa", $content);
    $content = str_replace('[montant_total_ht]', number_format($devi->prix_HT, 2) . " fcfa", $content);

    $montant_tva = $devi->prix_TTC - $devi->prix_HT;
    $content = str_replace('[montant_total_tva]', number_format($montant_tva, 2) . " fcfa", $content);

    // Gérer les échéances
    if ($devi->type_paiement == 'echeance') {
        $echeancesHtml = '
            <thead>
                <tr>
                    <th>Prévue le</th>
                    <th>Montant</th>
                </tr>
            </thead>';
        foreach ($devi->echeances as $echeance) {
            $echeancesHtml .= "
                <tbody>
                    <tr>
                        <td>" . \Carbon\Carbon::parse($echeance->date_pay_echeance)->format('d/m/Y') . "</td>
                        <td>" . number_format($echeance->montant_echeance, 2) . " fcfa</td>
                    </tr>
                </tbody>";
        }
        $content = str_replace('[echeances]', $echeancesHtml, $content);
    } else {
        $content = str_replace('[echeances]', '', $content);
    }

   // Gérer les signatures
   if($modelDocument->signatureExpediteurModel && $modelDocument->image_expediteur){
        // 1. Créer le chemin complet vers l'image
        $logoPath = storage_path('app/public/' . $modelDocument->image_expediteur);

        // 2. Vérifier que l'image existe et renvoyer une erreur si elle est introuvable
        if (!file_exists($logoPath)) {
            return response()->json(['error' => 'L\'image de signature expéditeur est introuvable'], 404);
        }

        // 3. Lire le contenu de l'image et l'encoder en base64
        $logoData = base64_encode(file_get_contents($logoPath));

        // 4. Déterminer le type MIME en fonction de l'extension
        $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
        $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';

        // 5. Créer le HTML avec l'image encodée en base64 pour l'intégrer dans le contenu PDF
        if ($modelDocument->signatureExpediteurModel && $modelDocument->image_expediteur ) {
            $signatureExpediteurTitre="signature Expediteur";
            $mention_expediteur="Mention Expediteur";
            $signatureExpediteurHtml = "<img style='max-width: 100%;margin-top: 10px; height: auto;' src='data:$mimeType;base64,$logoData' alt='Signature Expéditeur' />";
            $content .= "<div style='margin-top: 20px;'>
                        <span>{$mention_expediteur}: </span>
                        <span>{$modelDocument->mention_expediteur}</span>
                        <p>{$signatureExpediteurTitre}</p>
                        <p>{$signatureExpediteurHtml}</p>
                </div>";
        }else {
            $content .= "";
        }
    }
        if ($modelDocument->signatureDestinataireModel) {
            $mention_destinataire="Mention Destinataire";
            $content .= "<div style='margin-top: 20px; text-align: right;'>
            <span>{$mention_destinataire}: </span>
            <span>{$modelDocument->mention_destinataire}</span>
          </div>";
        } else {
            $content .= "";
        }

    // Gérer autre image
    if($modelDocument->autresImagesModel && $modelDocument->image){

     // 1. Créer le chemin complet vers l'image
     $autreImagePath = storage_path('app/public/' . $modelDocument->image);

     // 2. Vérifier que l'image existe et renvoyer une erreur si elle est introuvable
     if (!file_exists($autreImagePath)) {
         return response()->json(['error' => 'L\'image de signature expéditeur est introuvable'], 404);
     }

     // 3. Lire le contenu de l'image et l'encoder en base64
     $logoData = base64_encode(file_get_contents($autreImagePath));

     // 4. Déterminer le type MIME en fonction de l'extension
     $extension = pathinfo($autreImagePath, PATHINFO_EXTENSION);
     $mimeType = ($extension === 'png') ? 'image/png' : 'image/jpeg';

      // 5. Créer le HTML avec l'image encodée en base64 pour l'intégrer dans le contenu PDF
      if ($modelDocument->autresImagesModel && $modelDocument->image ) {
        $AutreImageTitre="Autre Image";
        $AutreImageTitreHtml = "<img style='max-width: 100%;margin-top: 10px; height: auto;' src='data:$mimeType;base64,$logoData' alt='Signature Expéditeur' />";
        $content .= "<div style='margin-top: 20px;'>
                    <p>{$AutreImageTitre}</p>
                    <p>{$AutreImageTitreHtml}</p>
            </div>";
    }else {
        $content .= "";
    }
    }
    // Gérer les conditions de paiement
        if ($modelDocument->conditionsPaiementModel) {
            $content .= "
            <div style='margin-top: 20px; text-align: right;'>
            <h4>Conditions de paiement</h4>
            <span>{$modelDocument->conditionPaiement}</span>
          </div>";
        } else {
            $content .= "";
        }               


    // Gérer les coordonnées bancaires
        if ($modelDocument->coordonneesBancairesModel) {
            $coordonneesBancairesHtml = "<h4>Coordonnées bancaires</h4>
                <p>Titulaire du compte : {$modelDocument->titulaire_compte}</p>
                <p>IBAN : {$modelDocument->IBAN}</p>
                <p>BIC : {$modelDocument->BIC}</p>";
                $content .= "<div style='margin-top: 20px;'>
                <span>{$coordonneesBancairesHtml}</span>
              </div>";
        } else {
            $content .= "";
        }

    // Gérer la note de pied de page
        if ($modelDocument->notePiedPageModel) {
            $content .= "<div style='position: fixed; bottom: 0; left: 0; width: 100%; margin: 0;  border-top: 1px solid #eee;'>
           <span>{$modelDocument->peidPage}</span>
        </div>";
        } else {
            $content .= "";
        }

    // 3. Appliquer le CSS du modèle en ajoutant une structure HTML complète
    $css = $modelDocument->css;
    $content = "<!doctype html>
    <html lang='fr'>
    <head>
        <meta charset='utf-8'>
        <style>{$css}</style>
    </head>
    <body>
        {$content}
    </body>
    </html>";

    // 4. Configurer DOMPDF avec des options avancées et générer le PDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isCssFloatEnabled', true);
    
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($content);
    $dompdf->setPaper('A4', 'portrait');

    $dompdf->render();

    $pdfDirectory = storage_path('app/public/devis');
    $pdfPath = $pdfDirectory . '/devi_' . $devi->num_devi . '.pdf';

    // Vérifiez et créez le dossier si nécessaire
    if (!file_exists($pdfDirectory)) {
        mkdir($pdfDirectory, 0777, true); // true pour créer les sous-dossiers nécessaires
    }

file_put_contents($pdfPath, $dompdf->output());

return $pdfPath;
    

}

}
 