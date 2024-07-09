<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\FactureRecurrente;
use App\Services\NumeroGeneratorService;
use App\Models\ArtcleFacture;
use App\Models\Echeance;
use App\Models\Facture;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $facturesRecurrentes = FactureRecurrente::all();
            foreach ($facturesRecurrentes as $factureRecurrente) {
                $nombrePeriodes = $factureRecurrente->nombre_periode;
                $periode = $factureRecurrente->periode;
                $dateDebut = \Carbon\Carbon::parse($factureRecurrente->date_debut);

                $nextDate = $dateDebut;
                while ($nextDate <= now()) {
                    $nextDate = match($periode) {
                        'jour' => $nextDate->addDays($nombrePeriodes),
                        'semaine' => $nextDate->addWeeks($nombrePeriodes),
                        'mois' => $nextDate->addMonths($nombrePeriodes),
                        default => $nextDate,
                    };

                    if ($nextDate <= now()) {
                        $this->genererFacture($factureRecurrente, $factureRecurrente->user_id, $factureRecurrente->sousUtilisateur_id, $factureRecurrente->articles, $nextDate);
                    }
                }
            }
        })->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Function to generate a facture.
     */
    protected function genererFacture($factureRecurrente, $userId, $sousUtilisateurId, $articles, $nextDate)
    {
        $typeDocument = 'facture';
        $numFacture = NumeroGeneratorService::genererNumero($userId, $typeDocument);
    
        $facture = Facture::create([
            'num_fact' => $numFacture,
            'client_id' => $factureRecurrente->client_id,
            'date_creation' => now(),
            'date_paiement' => $nextDate,
            'active_Stock' => $factureRecurrente->active_Stock ?? 'oui',
            'prix_HT' => $factureRecurrente->prix_HT,
            'prix_TTC' => $factureRecurrente->prix_TTC,
            'note_fact' => $factureRecurrente->note_interne,
            'archiver' => 'non',
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
            'type_paiement' => 'echeance',
            'statut_paiement' => 'en_attente',
            'id_paiement' => null,
        ]);
    
        foreach ($articles as $articleData) {
            ArtcleFacture::create([
                'id_facture' => $facture->id,
                'id_article' => $articleData['id_article'],
                'quantite_article' => $articleData['quantite_article'],
                'prix_unitaire_article' => $articleData['prix_unitaire_article'],
                'TVA_article' => $articleData['TVA_article'] ?? 0,
                'reduction_article' => $articleData['reduction_article'] ?? 0,
                'prix_total_article' => $articleData['prix_total_article'] ?? 0,
                'prix_total_tva_article' => $articleData['prix_total_tva_article'] ?? 0,
            ]);
        }
    
        Echeance::create([
            'facture_id' => $facture->id,
            'date_pay_echeance' => $nextDate,
            'montant_echeance' => $facture->prix_TTC,
            'sousUtilisateur_id' => $sousUtilisateurId,
            'user_id' => $userId,
        ]);
    
        if ($factureRecurrente->envoyer_mail) {
            // Implémenter la logique pour envoyer un email avec la facture.
        }
    
        return $facture;   
    
    }
}
