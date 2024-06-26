<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PromoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('promos')->insert([
            [
            'nom_promo' => 'Promo Été',
            'pourcentage_promo' => 20.00,
            'date_expiration' => Carbon::now()->addMonths(3),
            'sousUtilisateur_id' => null, 
            'user_id' => 1, 
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'nom_promo' => 'Promo hiver',
            'pourcentage_promo' => 50.00,
            'date_expiration' => Carbon::now()->addMonths(3),
            'sousUtilisateur_id' => null, 
            'user_id' => 1, 
            'created_at' => now(),
            'updated_at' => now(),
            ]
        ]);
    }
}
