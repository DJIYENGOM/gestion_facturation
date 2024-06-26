<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorieClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categorie_clients')->insert([
            'nom_categorie' => 'Client Premium',
            'sousUtilisateur_id' => null,
            'user_id' => 1, 
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
