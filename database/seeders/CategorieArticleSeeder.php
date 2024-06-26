<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorieArticleSeeder extends Seeder
{
    public function run()
    {
        DB::table('categorie_articles')->insert([
            [
                'nom_categorie_article' => 'Électronique',
                'type_categorie_article' => 'produit',
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 1, // Assurez-vous que cet ID existe dans la table `users`
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom_categorie_article' => 'Consultation',
                'type_categorie_article' => 'service',
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 2, // Assurez-vous que cet ID existe dans la table `users`
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
