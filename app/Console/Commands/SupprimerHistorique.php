<?php

namespace App\Console\Commands;

use App\Models\Historique;
use Illuminate\Console\Command;

class SupprimerHistorique extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:supprimer_historique';

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
        Historique::where('created_at', '<', now()->subDay())->delete();
    }
}
