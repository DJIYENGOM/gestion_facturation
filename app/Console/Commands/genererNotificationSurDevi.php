<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Devi;
use App\Models\Notification;
use Illuminate\Console\Command;
use App\Models\MessageNotification;

class genererNotificationSurDevi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:generer_notification_sur_devi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $configurations = Notification::where('devis_expirer', true)
            ->where('nombre_jourNotif_devi', '>=', 1)
            ->get();

        foreach ($configurations as $config) {
            $nombre_jour = $config->nombre_jourNotif_devi;

            $devis = Devi::where(function($query) use ($config) {
                    $query->where('user_id', $config->user_id)
                          ->orWhere('sousUtilisateur_id', $config->sousUtilisateur_id);
                })
                ->get();

            foreach ($devis as $devi) {
                $date_paiement = Carbon::parse($devi->date_limite);
               
                if ($date_paiement->isToday()) {
                    $this->creerNotification($config, 'Devis prevues aujourd\'hui', $devi);
                } elseif ($date_paiement->diffInDays($now) == $nombre_jour) {
                    $message = "Devis à payer dans les {$nombre_jour} prochains jours";
                    $this->creerNotification($config, $message, $devi);
                }
            }
        }
    }

    /**
     * Crée une notification dans la table MessageNotification.
     */
    private function creerNotification($config, $message, $devi)
    {
        $notification = new MessageNotification();
        $notification->message = $message;
        $notification->user_id = $config->user_id;
        $notification->sousUtilisateur_id = $config->sousUtilisateur_id;
        $notification->devis_id = $devi->id;
        $notification->save();

        // $this->info('Notification créée: ' . $message);
    
    }
}
