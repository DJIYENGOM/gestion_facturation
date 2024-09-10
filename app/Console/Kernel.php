<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
         $schedule->command('command:factureRecurrente')->daily();
         
         $schedule->command('notification:genererDepense')->daily();

         $schedule->command('notification:genererNotificationEcheance')->daily();

         $schedule->command('notification:generer_notification_sur_devi')->daily();

         $schedule->command('command:supprimer_historique')->daily();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}