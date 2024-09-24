<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfigurationRelanceAuto;
use Illuminate\Support\Facades\Validator;

class ConfigurationRelanceAutoController extends Controller
{
   public function ConfigurerRelanceAuto(Request $request)
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
        'envoyer_rappel_avant'=> 'nullable|boolean',
        'nombre_jour_avant'=> 'nullable|integer',
        'envoyer_rappel_apres'=> 'nullable|boolean',
        'nombre_jour_apres'=> 'nullable|integer',
        
    ]);
    
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $config = new ConfigurationRelanceAuto([
        'envoyer_rappel_avant'=> $request->envoyer_rappel_avant ?? false,
        'nombre_jour_avant'=> $request->nombre_jour_avant ?? 5,
        'envoyer_rappel_apres'=> $request->envoyer_rappel_apres ?? false,
        'nombre_jour_apres'=> $request->nombre_jour_apres ?? 5,
        'sousUtilisateur_id'=> $sousUtilisateur_id,
        'user_id'=> $userId

    ]);
    $config->save();
    return response()->json($config, 201);

   }

}
