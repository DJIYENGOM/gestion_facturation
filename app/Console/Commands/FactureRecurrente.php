<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Facture;
use Illuminate\Console\Command;

class FactureRecurrente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:factureRecurrente';

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
     $factures = Facture::all();
     foreach ($factures as $facture) {
         $factureRecurrente = $facture->factureRcurrente;

         if ($factureRecurrente != null) {
          $periode=$factureRecurrente->periode;
          $nombre_periode=$factureRecurrente->nombre_periode;
          switch ($periode) {
            case 'jour':
                if ($facture->date_debut >= Carbon::now()->addDays($nombre_periode)) {

                    
                }
               
                break;
            case 'semaine':
               
                break;
            case 'mois':
              
                break;
            default:
     }
    }

}
}
}
