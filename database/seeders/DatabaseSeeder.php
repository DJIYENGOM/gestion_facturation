<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
        UserSeeder::class,
        PayementSeeder::class,
        PromoSeeder::class,
        SousUtilisateurSeeder::class,
        CategorieArticleSeeder::class,
        ArticleSeeder::class,
        CategorieClientSeeder::class,
        ClientSeeder::class,
        FactureSeeder::class,
        // Ajoutez d'autres seeders nécessaires
    ]);
    }
}
