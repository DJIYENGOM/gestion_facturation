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
        $userId = $sousUtilisateur->id_user; // ID de l'utilisateur parent
    } elseif (auth()->check()) {
        $userId = auth()->id();
        $sousUtilisateur_id = null;
    } else {
        return response()->json(['error' => 'Vous n\'êtes pas connecté'], 401);
    }

    $validator = Validator::make($request->all(), [
        'envoyer_rappel_avant' => 'nullable|boolean',
        'nombre_jour_avant' => 'nullable|integer|min:0',
        'envoyer_rappel_apres' => 'nullable|boolean',
        'nombre_jour_apres' => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Vérification si une configuration existe déjà pour cet utilisateur/sous-utilisateur
    $config = ConfigurationRelanceAuto::where('user_id', $userId)
        ->when($sousUtilisateur_id, function ($query) use ($sousUtilisateur_id) {
            return $query->orWhere('sousUtilisateur_id', $sousUtilisateur_id);
        })
        ->first();

    // Si une configuration existe, on la met à jour. Sinon, on en crée une nouvelle.
    if ($config) {
        $config->update([
            'envoyer_rappel_avant' => $request->envoyer_rappel_avant ?? false,
            'nombre_jour_avant' => $request->nombre_jour_avant ?? $config->nombre_jour_avant,
            'envoyer_rappel_apres' => $request->envoyer_rappel_apres ?? false,
            'nombre_jour_apres' => $request->nombre_jour_apres ?? $config->nombre_jour_apres,
        ]);
    } else {
        $config = ConfigurationRelanceAuto::create([
            'envoyer_rappel_avant' => $request->envoyer_rappel_avant ?? false,
            'nombre_jour_avant' => $request->nombre_jour_avant ?? 5,
            'envoyer_rappel_apres' => $request->envoyer_rappel_apres ?? false,
            'nombre_jour_apres' => $request->nombre_jour_apres ?? 5,
            'sousUtilisateur_id' => $sousUtilisateur_id,
            'user_id' => $userId,
        ]);
    }

    return response()->json($config, 200);
}


}
