<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('payements')->insert([
            [
            'nom_payement' => 'Paiement par Carte',
            'sousUtilisateur_id' => null, 
            'user_id' => 1, 
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'nom_payement' => 'Paiement par Carte',
            'sousUtilisateur_id' => null, 
            'user_id' => 1, 
            'created_at' => now(),
            'updated_at' => now(),
        ],
        ]);
    }
}
