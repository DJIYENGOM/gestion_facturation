<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Facture;
use App\Models\Echeance;
use App\Models\Historique;
use App\Models\PaiementRecu;
use Illuminate\Http\Request;
use App\Models\ModelDocument;
use Illuminate\Support\Facades\Validator;

class PaiementRecuController extends Controller
{
    public function ajouterPaiementRecu(Request $request)
    {
        $request->validate([
            'facture_id' => 'required|exists:factures,id',
            'num_paiement' => 'nullable|string|max:255',
            'date_prevu' => 'nullable|date',
            'date_recu' => 'nullable|date',
            'montant' => 'required|numeric|min:0',
            'commentaire' => 'nullable|string',
            'id_paiement' => 'required|exists:payements,id',
        ]);

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

        $paiementRecu = PaiementRecu::create([
            'facture_id' => $request->facture_id,
            'num_paiement' => $request->input('num_paiement'),
            'date_prevu' => $request->input('date_prevu'),
            'date_recu' => $request->input('date_recu'),
            'montant' => $request->input('montant'),
            'commentaire' => $request->input('commentaire'),
            'id_paiement' => $request->input('id_paiement'),
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);

        return response()->json(['message' => 'Paiement recu ajouté avec succès', 'paiement_recu' => $paiementRecu], 201);
    }

    public function listPaiementsRecusParFacture($factureId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
              return response()->json(['error' => 'Accès non autorisé'], 403);
              }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;

            $paiementsRecus = PaiementRecu::where('facture_id', $factureId)
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->get();
        } elseif (auth()->check()) {
            $userId = auth()->id();

            $paiementsRecus = PaiementRecu::where('facture_id', $factureId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateur', function($subQuery) use ($userId) {
                              $subQuery->where('id_user', $userId);
                          });
                })
                ->get();
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }

        return response()->json(['paiements_recus' => $paiementsRecus], 200);
    }

    public function supprimerPaiementRecu($paiementRecuId)
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateur = auth('apisousUtilisateur')->user();
            if (!$sousUtilisateur->fonction_admin) {
                return response()->json(['error' => 'Action non autorisée pour Vous'], 403);
            }
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user;

            $paiementRecu = PaiementRecu::where('id', $paiementRecuId)
                ->where(function ($query) use ($sousUtilisateurId, $userId) {
                    $query->where('sousUtilisateur_id', $sousUtilisateurId)
                          ->orWhere('user_id', $userId);
                })
                ->first();

            if ($paiementRecu) {
                $paiementRecu->delete();
                return response()->json(['message' => 'Paiement recu supprimé avec succès'], 200);
            } else {
                return response()->json(['error' => 'Ce sous-utilisateur ne peut pas supprimer ce paiement recu'], 403);
            }
        } elseif (auth()->check()) {
            $userId = auth()->id();

            $paiementRecu = PaiementRecu::where('id', $paiementRecuId)
                ->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('sousUtilisateur', function($subQuery) use ($userId) {
                              $subQuery->where('id_user', $userId);
                          });
                })
                ->first();

            if ($paiementRecu) {
                $paiementRecu->delete();
                return response()->json(['message' => 'Paiement recu supprimé avec succès'], 200);
            } else {
                return response()->json(['error' => 'Cet utilisateur ne peut pas supprimer ce paiement recu'], 403);
            }
        } else {
            return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
        }
    }

    public function transformerPaiementRecuEnEcheance($paiementRecuId)
{
    // Vérifiez les permissions de l'utilisateur
    if (auth()->guard('apisousUtilisateur')->check()) {
        $sousUtilisateur = auth('apisousUtilisateur')->user();
        if (!$sousUtilisateur->visibilite_globale && !$sousUtilisateur->fonction_admin) {
          return response()->json(['error' => 'Accès non autorisé'], 403);
          }

        $sousUtilisateurId = auth('apisousUtilisateur')->id();
        $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateurId = null;
    } else {
        return response()->json(['error' => 'Vous n\'etes pas connecté'], 401);
    }

    // Récupérer le paiement recu
    $paiementRecu = PaiementRecu::find($paiementRecuId);
    if (!$paiementRecu) {
        return response()->json(['error' => 'Paiement recu non trouvé'], 404);
    }

    // Créez une nouvelle échéance à partir des informations du paiement recu
    $echeance = Echeance::create([
        'facture_id' => $paiementRecu->facture_id,
        'date_pay_echeance' => $paiementRecu->date_prevu,
        'montant_echeance' => $paiementRecu->montant,
        'commentaire' => null,
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
    ]);

    $facture=Facture::find($paiementRecu->facture_id);
    $facture->update([
        'statut_paiement' =>'en_attente',
    ]);
    // Supprimez le paiement recu
    $paiementRecu->delete();

    Historique::create([
        'sousUtilisateur_id' => $sousUtilisateurId,
        'user_id' => $userId,
        'message' => 'Des Paiements Recus ont été transformées en echeances',
        'id_facture' => $facture->id
    ]);

    return response()->json([
        'message' => 'Paiement recu transformé en échéance avec succès',
        'echeance' => $echeance
    ], 201);
}

public function RapportPaiementRecu(Request $request)
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
    
    $paiements = PaiementRecu::with(['paiement', 'facture.client'])
    ->whereBetween('date_recu', [$dateDebut, $dateFin])
    ->where(function ($query) use ($userId, $parentUserId) {
        $query->where('user_id', $userId)
            ->orWhere('user_id', $parentUserId)
            ->orWhereHas('sousUtilisateur', function ($query) use ($parentUserId) {
                $query->where('id_user', $parentUserId);
            });
    })
    ->get();

    return response()->json($paiements);
}


public function genererPDFPaiementRecu($paiementRecuId, $factureId, $modelDocumentId)
{
    $facture = Facture::with(['user', 'client', 'articles.article', 'paiement'])->find($factureId);
    $paiementRecu = PaiementRecu::find($paiementRecuId);
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
 else {
    $content = str_replace('[logo]', '', $content);
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
$content = str_replace('[articles]', $articlesHtml, $content);

// Gérer le montant total
$content = str_replace('[montant_total_ttc]', number_format($facture->prix_TTC, 2) . " fcfa", $content);
$content = str_replace('[montant_total_ht]', number_format($facture->prix_HT, 2) . " fcfa", $content);

$montant_tva = $facture->prix_TTC - $facture->prix_HT;
$content = str_replace('[montant_total_tva]', number_format($montant_tva, 2) . " fcfa", $content);


$recuHtml = '';
$recuHtml .= "<tr>
        <td>{$paiementRecu->date_recu} <br> {$facture->paiement->nom_payement } <br> ". number_format($paiementRecu->montant, 2) . "</td> 
   
     </tr>";

$content = str_replace('[paiementRecu]', $recuHtml, $content);



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


    
$pdfContent = $dompdf->output();
    
$filename = 'Reçu_' . $paiementRecu->num_paiement . '.pdf';

return response($pdfContent)
    ->header('Content-Type', 'application/pdf')
    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
    ->header('Access-Control-Allow-Origin', '*')
    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
    ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization')
    ->header('Access-Control-Allow-Credentials', 'true')
    ->header('Access-Control-Expose-Headers', 'Content-Disposition');

}

}
