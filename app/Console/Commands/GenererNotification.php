<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Depense;
use App\Models\MessageNotification;
use App\Models\Notification;
use Carbon\Carbon;

class GenererNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:genererDepense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des notifications pour les dépenses impayées en fonction des configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $configurations = Notification::where('depense_impayer', true)
            ->where('nombre_jourNotif_depense', '>=', 1)
            ->get();

        foreach ($configurations as $config) {
            $nombre_jour = $config->nombre_jourNotif_depense;

            // Rechercher les dépenses impayées en fonction de la date de paiement
            $depenses = Depense::where(function($query) use ($config) {
                    $query->where('user_id', $config->user_id)
                          ->orWhere('sousUtilisateur_id', $config->sousUtilisateur_id);
                })
                ->where('statut_depense', '!=', 'payer')
                ->get();

            foreach ($depenses as $depense) {
                $date_paiement = Carbon::parse($depense->date_paiement);
                $depense_id = $depense->id;

                if ($date_paiement->isToday()) {
                    // Créer un message pour les dépenses à payer aujourd'hui
                    $this->creerNotification($config, 'Nouvelles dépenses à payer aujourd\'hui', $depense_id);
                } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                    // Créer un message pour les dépenses à payer dans les prochains jours
                    $message = "Dépenses à payer dans les {$nombre_jour} prochains jours";
                    $this->creerNotification($config, $message, $depense_id);
                }
            }
        }
    }

    /**
     * Crée une notification dans la table MessageNotification.
     */
    private function creerNotification($config, $message, $depense_id)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->depense_id = $depense_id;
        $notification->save();

        // $this->info('Notification créée: ' . $message);
    }
}
