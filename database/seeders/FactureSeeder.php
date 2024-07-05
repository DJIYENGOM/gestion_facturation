<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class FactureSeeder extends Seeder
{

    public function run()
    {
        DB::table('factures')->insert([
            [
                'num_fact' => 'FACT001',
                'date_creation' => '2024-06-01',
                'reduction_facture' => 50.00,
                'prix_HT' => 500.00,
                'prix_TTC' => 600.00,
                'note_fact' => 'Première facture avec remise.',
                'date_paiement' => '2024-06-15',
                'active_Stock' => 'oui',
                'statut_paiement' => 'payer',
                'archiver' => 'non',
                'type_paiement' => 'immediat',
                'client_id' => 1,
                'id_comptable' => 1,
                'id_paiement' => 1,
                'sousUtilisateur_id' => null,
                'user_id' => 1,
                'devi_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'num_fact' => 'FACT002',
                'date_creation' => '2024-06-05',
                'reduction_facture' => 0.00,
                'prix_HT' => 700.00,
                'prix_TTC' => 840.00,
                'note_fact' => 'Facture sans remise.',
                'date_paiement' => null,
                'active_Stock' => 'non',
                'statut_paiement' => 'en_attente',
                'archiver' => 'non',
                'type_paiement' => 'echeance',
                'client_id' => 1,
                'id_comptable' => 2,
                'id_paiement' => 1,
                'sousUtilisateur_id' => null,
                'user_id' => 2,
                'devi_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

}
