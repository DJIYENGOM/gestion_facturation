<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelDocument;
use Illuminate\Support\Facades\Validator;

class ModelDocumentController extends Controller
{
        public function CreerModelDocument(Request $request)
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
    
            $validator = Validator::make($request->all(), [
                'typeDocument' => 'required|in:vente,devi,livraison,command_vente,command_achat',
                'reprendre_model_vente'=>'nullable',
                'typeDesign' => 'required|in:bloc,compact,photo',
                'contenu' => 'required',
                'signatureExpediteurModel' => 'required|boolean',
                'mention_expediteur' => 'nullable|string',
                'image_expediteur' => 'nullable|string',
                'signatureDestinataireModel' => 'required|boolean',
                'mention_destinataire' => 'nullable|string',
                'autresImagesModel' => 'required|boolean',
                'image.*' => 'nullable|string',
                'conditionsPaiementModel' => 'required|boolean',
                'conditionPaiement' => 'nullable|string',
                'coordonneesBancairesModel' => 'required|boolean',
                'titulaireCompte' => 'nullable|string',
                'IBAN' => 'nullable|string',
                'BIC' => 'nullable|string',
                'notePiedPageModel' => 'required|boolean',
                'peidPage' => 'nullable|string',
                'css' => 'nullable'
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
    
            $modelDocument = new ModelDocument([
                'typeDocument' => $request->typeDocument,
                'reprendre_model_vente'=>$request->reprendre_model_vente,
                'typeDesign'=>$request->typeDesign,
                'content' => $request->contenu,
                'signatureExpediteurModel' => $request->signatureExpediteurModel,
                'mention_expediteur' => $request->mention_expediteur,
                'image_expediteur' => $request->image_expediteur,
                'signatureDestinataireModel' => $request->signatureDestinataireModel,
                'mention_destinataire' => $request->mention_destinataire,
                'autresImagesModel' => $request->autresImagesModel,
                'image' => $request->image,
                'conditionsPaiementModel' => $request->conditionsPaiementModel,
                'conditionPaiement' => $request->conditionPaiement,
                'coordonneesBancairesModel' => $request->coordonneesBancairesModel,
                'titulaireCompte' => $request->titulaireCompte,
                'IBAN' => $request->IBAN,
                'BIC' => $request->BIC,
                'notePiedPageModel' => $request->notePiedPageModel,
                'peidPage' => $request->peidPage,
                'css' => $request->css,
                'sousUtilisateur_id' => $sousUtilisateur_id,
                'user_id' => $userId
            ]);
    
            $modelDocument->save();
    
            return response()->json(['message' => 'Modèle de document créé avec succès', 'data' => $modelDocument], 201);
        }

        public function ModifierModelDocument(Request $request, $id)
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

    $modelDocument = ModelDocument::find($id);

    if (!$modelDocument) {
        return response()->json(['error' => 'Modèle de document non trouvé'], 404);
    }

    // Validation des données
    $validator = Validator::make($request->all(), [
        'typeDocument' => 'required|in:vente,devi,livraison,command_vente,command_achat',
        'reprendre_model_vente'=>'nullable',
        'typeDesign' => 'required|in:bloc,compact,photo',
        'contenu' => 'required',
        'signatureExpediteurModel' => 'required|boolean',
        'mention_expediteur' => 'nullable|string',
        'image_expediteur' => 'nullable|string',
        'signatureDestinataireModel' => 'required|boolean',
        'mention_destinataire' => 'nullable|string',
        'autresImagesModel' => 'required|boolean',
        'image' => 'nullable|string',
        'conditionsPaiementModel' => 'required|boolean',
        'conditionPaiement' => 'nullable|string',
        'coordonneesBancairesModel' => 'required|boolean',
        'titulaireCompte' => 'nullable|string',
        'IBAN' => 'nullable|string',
        'BIC' => 'nullable|string',
        'notePiedPageModel' => 'required|boolean',
        'peidPage' => 'nullable|string',
        'css' => 'nullable'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $modelDocument->typeDesign = $request->typeDesign;
    $modelDocument->reprendre_model_vente = $request->reprendre_model_vente ; 
    $modelDocument->typeDocument = $request->typeDocument;
    $modelDocument->content = $request->contenu;
    $modelDocument->signatureExpediteurModel = $request->signatureExpediteurModel;
    $modelDocument->mention_expediteur = $request->mention_expediteur;
    $modelDocument->image_expediteur = $request->image_expediteur;
    $modelDocument->signatureDestinataireModel = $request->signatureDestinataireModel;
    $modelDocument->mention_destinataire = $request->mention_destinataire;
    $modelDocument->autresImagesModel = $request->autresImagesModel;
    $modelDocument->image = $request->image;
    $modelDocument->conditionsPaiementModel = $request->conditionsPaiementModel;
    $modelDocument->conditionPaiement = $request->conditionPaiement;
    $modelDocument->coordonneesBancairesModel = $request->coordonneesBancairesModel;
    $modelDocument->titulaireCompte = $request->titulaireCompte;
    $modelDocument->IBAN = $request->IBAN;
    $modelDocument->BIC = $request->BIC;
    $modelDocument->notePiedPageModel = $request->notePiedPageModel;
    $modelDocument->peidPage = $request->peidPage;
    $modelDocument->css = $request->css;
    $modelDocument->sousUtilisateur_id = $sousUtilisateur_id;
    $modelDocument->user_id = $userId;

    $modelDocument->save();

    return response()->json(['message' => 'Modèle de document mis à jour avec succès', 'data' => $modelDocument], 200);
}

public function listerModelesDocumentsParType(Request $request, $typeDocument)
{
    $validator = Validator::make(['typeDocument' => $typeDocument], [
        'typeDocument' => 'required|in:vente,devi,livraison,command_vente,command_achat' 
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
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
    $modelesDocuments = ModelDocument::where('typeDocument', $typeDocument)
    ->where('sousUtilisateur_id', $sousUtilisateur_id)
    ->orWhere('user_id', $userId)
    ->get();

    $nombreTotalModeles = $modelesDocuments->count();

    return response()->json([
        'message' => 'Liste des modèles de documents pour le type ' . $typeDocument,
        'nombreTotalModeles' => $nombreTotalModeles,
        'modelesDocuments' => $modelesDocuments
    ], 200);
}

}
