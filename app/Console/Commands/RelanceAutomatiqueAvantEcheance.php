<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Echeance;
use App\Http\Controllers\EmailModeleController;
use App\Models\ConfigurationRelanceAuto;
use Carbon\Carbon;

class RelanceAutomatiqueAvantEcheance extends Command
{
    protected $signature = 'command:avant-echeance';

    protected $description = 'Envoyer un email de relance un jour avant la date d\'échéance';

    protected $EmailModeleController;

    public function __construct(EmailModeleController $EmailModeleController)
    {
        parent::__construct();
        $this->EmailModeleController = $EmailModeleController;
    }

    public function handle()
    {
        if (auth()->guard('apisousUtilisateur')->check()) {
            $sousUtilisateurId = auth('apisousUtilisateur')->id();
            $userId = auth('apisousUtilisateur')->user()->id_user; // ID de l'utilisateur parent
    
            $config = ConfigurationRelanceAuto::where('sousUtilisateur_id', $sousUtilisateurId)
                ->orWhere('user_id', $userId)
                ->first();
        } elseif (auth()->check()) {
            $userId = auth()->id();

            $config = ConfigurationRelanceAuto::where('user_id', $userId)
                ->orWhereHas('sousUtilisateur', function($query) use ($userId) {
                    $query->where('id_user', $userId);
                })
                ->first();
        }

        if($config && $config->envoyer_rappel_avant==1){
            
        $echeances = Echeance::whereDate('date_pay_echeance', Carbon::now()->addDays($config->nombre_jour_avant))->get();

        foreach ($echeances as $echeance) {
            $this->EmailModeleController->envoyerEmailRelanceAvantEcheance($echeance->id);
            $this->info('Email de relance avant échéance envoyé pour l\'échéance ID ' . $echeance->id);
        }

        $this->info('Toutes les emails de relance avant éléance ont été envoyés');
    }
    }
}

