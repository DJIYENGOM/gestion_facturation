<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Facture;
use App\Models\Echeance;
use Illuminate\Console\Command;
use App\Services\NumeroGeneratorService;

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
         // dd($factureRecurrente);

          $periode=$factureRecurrente->periode;
          $nombre_periode=$factureRecurrente->nombre_periode;
          switch ($periode) {
            case 'jour':
                if ($facture->date_debut <= Carbon::now()->addDays($nombre_periode)) {
                   // dd('ok');
                 $newFacture = new Facture();  


                 $typeDocument = 'facture';
                 $numFacture = NumeroGeneratorService::genererNumero($facture->user_id, $typeDocument);
                $newFacture->client_id = $facture->client_id;
                $newFacture->num_facture = $numFacture;
                $newFacture->type_facture = $facture->type_facture;
                $newFacture->date_creation = now();
                $newFacture->date_paiement = $facture->date_debut;
                $newFacture->reduction_facture = $facture->reduction_facture;
                $newFacture->active_Stock = $facture->active_Stock;
                $newFacture->prix_HT = $facture->prix_HT;
                $newFacture->prix_TTC = $facture->prix_TTC;
                $newFacture->note_fact = $facture->note_fact;
                $newFacture->archiver = 'non';
                $newFacture->sousUtilisateur_id = $facture->sousUtilisateur_id;
                $newFacture->user_id = $facture->user_id;
                $newFacture->type_paiement = $facture->type_paiement;
                $newFacture->statut_paiement = $facture->statut_paiement;
                $newFacture->id_paiement = $facture->id_paiement;
                $newFacture->save();
                
                $newEcheance = new Echeance();
                $newEcheance->facture_id = $newFacture->id;
                $newEcheance->date_pay_echeance = $newFacture->date_paiement;
                $newEcheance->montant_echeance = $newFacture->prix_TTC;
                $newEcheance->sousUtilisateur_id = $newFacture->sousUtilisateur_id;
                $newEcheance->user_id = $newFacture->user_id;
                $newEcheance->save();
                //return dd($newFacture);
                
                
                }
               
                break;
            case 'semaine':
                if ($facture->date_debut <= Carbon::now()->addWeeks($nombre_periode)) {
                    $newFacture = new Facture();   

                    $typeDocument = 'facture';
                    $numFacture = NumeroGeneratorService::genererNumero($facture->user_id, $typeDocument);
                   $newFacture->client_id = $facture->client_id;
                   $newFacture->num_facture = $numFacture;
                   $newFacture->type_facture = $facture->type_facture;
                   $newFacture->date_creation = now();
                   $newFacture->date_paiement = $facture->date_debut;
                   $newFacture->reduction_facture = $facture->reduction_facture;
                   $newFacture->active_Stock = $facture->active_Stock;
                   $newFacture->prix_HT = $facture->prix_HT;
                   $newFacture->prix_TTC = $facture->prix_TTC;
                   $newFacture->note_fact = $facture->note_fact;
                   $newFacture->archiver = 'non';
                   $newFacture->sousUtilisateur_id = $facture->sousUtilisateur_id;
                   $newFacture->user_id = $facture->user_id;
                   $newFacture->type_paiement = $facture->type_paiement;
                   $newFacture->statut_paiement = $facture->statut_paiement;
                   $newFacture->id_paiement = $facture->id_paiement;
                   $newFacture->save();

                  // return $newFacture;
                  $newEcheance = new Echeance();
                  $newEcheance->facture_id = $newFacture->id;
                  $newEcheance->date_pay_echeance = $newFacture->date_paiement;
                  $newEcheance->montant_echeance = $newFacture->prix_TTC;
                  $newEcheance->sousUtilisateur_id = $newFacture->sousUtilisateur_id;
                  $newEcheance->user_id = $newFacture->user_id;
                  $newEcheance->save();
                    
                }
                break;
            case 'mois':
                if ($facture->date_debut <= Carbon::now()->addMonths($nombre_periode)) {
                    $newFacture = new Facture();   

                    $typeDocument = 'facture';
                    $numFacture = NumeroGeneratorService::genererNumero($facture->user_id, $typeDocument);
                   $newFacture->client_id = $facture->client_id;
                   $newFacture->num_facture = $numFacture;
                   $newFacture->type_facture = $facture->type_facture;
                   $newFacture->date_creation = now();
                   $newFacture->date_paiement = $facture->date_debut;
                   $newFacture->reduction_facture = $facture->reduction_facture;
                   $newFacture->active_Stock = $facture->active_Stock;
                   $newFacture->prix_HT = $facture->prix_HT;
                   $newFacture->prix_TTC = $facture->prix_TTC;
                   $newFacture->note_fact = $facture->note_fact;
                   $newFacture->archiver = 'non';
                   $newFacture->sousUtilisateur_id = $facture->sousUtilisateur_id;
                   $newFacture->user_id = $facture->user_id;
                   $newFacture->type_paiement = $facture->type_paiement;
                   $newFacture->statut_paiement = $facture->statut_paiement;
                   $newFacture->id_paiement = $facture->id_paiement;
                   $newFacture->save();

                  // return $newFacture;
                  $newEcheance = new Echeance();
                  $newEcheance->facture_id = $newFacture->id;
                  $newEcheance->date_pay_echeance = $newFacture->date_paiement;
                  $newEcheance->montant_echeance = $newFacture->prix_TTC;
                  $newEcheance->sousUtilisateur_id = $newFacture->sousUtilisateur_id;
                  $newEcheance->user_id = $newFacture->user_id;
                  $newEcheance->save();
                
                }
                break;
            default:
     }
    }

}
}
}
