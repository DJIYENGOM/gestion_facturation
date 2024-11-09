<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Echeance;
use App\Models\ModelDocument;
use Illuminate\Console\Command;
use App\Models\ConfigurationRelanceAuto;
use App\Http\Controllers\EmailModeleController; 

class RelanceAutomatiqueApresEcheance extends Command
{
    protected $signature = 'command:apres-echeance';
    protected $description = 'Envoyer un email de relance un jour après la date d\'échéance';

    protected $EmailModeleController; // Ajoute ceci pour injecter ton contrôleur

    public function __construct(EmailModeleController $EmailModeleController)
    {
        parent::__construct();
        $this->EmailModeleController = $EmailModeleController; // Injecte ton contrôleur
    }

    public function handle()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();

                $modelDocument = ModelDocument::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();

        } elseif (auth()->check()) {
            $userId = auth()->id();

            $config = ConfigurationRelanceAuto::where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();

                $modelDocument = ModelDocument::where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
        }

        if($config && $config->envoyer_rappel_apres==1){


        $echeances = Echeance::whereDate('date_pay_echeance', Carbon::now()->subDays($config->nombre_jour_apres))->get();

        if($echeances->facture){
        foreach ($echeances as $echeance) {


            $this->EmailModeleController->envoyerEmailRelanceApresEcheance($echeance->id, $modelDocument->id);

            $this->info('Email de relance après échéance envoyé pour l\'échéance ID ' . $echeance->id);
        }

        $this->info('Toutes les emails de relance après écheance ont été envoyés');
    }
}
}
}
