<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Echeance;
use App\Models\Notification;
use Illuminate\Console\Command;
use App\Models\MessageNotification;

class genererNotificationEcheance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:genererNotificationEcheance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère des notifications pour les echeances impayées en fonction des configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $configurations = Notification::where('payement_attente', true)
            ->where('nombre_jourNotif_echeance', '>=', 1)
            ->get();

        foreach ($configurations as $config) {
            $nombre_jour = $config->nombre_jourNotif_echeance;

            // Rechercher les dépenses impayées en fonction de la date de paiement
            $echeances = Echeance::where(function($query) use ($config) {
                    $query->where('user_id', $config->user_id)
                          ->orWhere('sousUtilisateur_id', $config->sousUtilisateur_id);
                })
                ->where('statut_paiement', '!=', 'payer')
                ->get();

            foreach ($echeances as $echeance) {
                $date_paiement = Carbon::parse($echeance->date_pay_echeance);
               
                if ($date_paiement->isToday()) {
                    // Créer un message pour les dépenses à payer aujourd'hui
                    $this->creerNotification($config, 'Echeances prevues aujourd\'hui', $echeance);
                } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                    // Créer un message pour les dépenses à payer dans les prochains jours
                    $message = "Echeances à payer dans les {$nombre_jour} prochains jours";
                    $this->creerNotification($config, $message, $echeance);
                }
            }
        }
    }

    /**
     * Crée une notification dans la table MessageNotification.
     */
    private function creerNotification($config, $message, $echeance)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->echeance_id = $echeance->id;
        $notification->facture_id= $echeance->facture_id ?? null;
        $notification->save();

        // $this->info('Notification créée: ' . $message);
    
    }
}
