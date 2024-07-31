<?php

namespace App\Services;

use App\Models\NumeroConfiguration;
use Carbon\Carbon;

class NumeroGeneratorService
{
    public static function genererNumero($userId, $typeDocument)
    {
        // Récupérer la configuration de numérotation pour l'utilisateur
        $configuration = NumeroConfiguration::where('user_id', $userId)
                                            ->where('type_document', $typeDocument)
                                            ->first();

        // Si aucune configuration n'est trouvée, retourner null ou générer une exception selon le besoin
        if (!$configuration) {
            return "pas de numero";
        }

        // Générer le numéro en fonction de la configuration
        $numero = '';

        if ($configuration->type_numerotation === 'avec_prefixe' && $configuration->prefixe) {
            $numero .= $configuration->prefixe;

            switch ($configuration->format ?? 'annee') { // Utiliser 'annee' comme format par défaut
                case 'annee':
                    $numero .= Carbon::now()->format('Y');
                    break;
                case 'annee_mois':
                    $numero .= Carbon::now()->format('Ym');
                    break;
                case 'annee_mois_jour':
                    $numero .= Carbon::now()->format('Ymd');
                    break;
            }
        }

        // Ajouter le compteur au numéro
        $numero .= str_pad($configuration->compteur, 6, '0', STR_PAD_LEFT);

        return $numero;
    }

    public static function incrementerCompteur($userId, $typeDocument)
    {
        $configuration = NumeroConfiguration::where('user_id', $userId)
                                            ->where('type_document', $typeDocument)
                                            ->first();

        if ($configuration) {
            $configuration->compteur++;
            $configuration->save();
        }
    }
}
