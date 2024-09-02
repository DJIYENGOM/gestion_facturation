<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Models\Notification;
use Illuminate\Console\Command;

class GenererNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:genererNotification';

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
     $echeances = Echeance::all();
    // dd($echeances);
     foreach ($echeances as $echeance) {
         
        if($echeance->date_pay_echeance == now()){
            $notification = new Notification();
            $notification->message = 'Echeances prevues Aujourd\'hui';
            $notification->user_id = $echeance->sousUtilisateur_id;
            $notification->sousUtilisateur_id = $echeance->sousUtilisateur_id;
            $notification->id_article = null;
            
            $notification->save();
        }
        dd($notification);
        
     }
        
    }
}
