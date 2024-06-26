<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    public function run()
    {
        DB::table('articles')->insert([
            [
                'num_article' => 'ART001',
                'nom_article' => 'Laptop Dell Inspiron',
                'description' => 'Un ordinateur portable Dell Inspiron avec un écran de 15 pouces.',
                'prix_unitaire' => 800.00,
                'quantite' => 10,
                'benefice' => 150.00,
                'prix_achat' => 650.00,
                'prix_promo' => 750.00,
                'prix_tva' => 960.00,
                'doc_externe' => 'manual.pdf',
                'tva' => 20.00,
                'benefice_promo' => 100.00,
                'quantite_alert' => 5,
                'type_article' => 'produit',
                'unité' => 'unite',
                'promo_id' => 1, // Assurez-vous que cet ID existe dans la table `promos`
                'id_comptable' => 1, // Assurez-vous que cet ID existe dans la table `compte_comptables`
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 1, // Assurez-vous que cet ID existe dans la table `users`
                'id_categorie_article' => 1, // Assurez-vous que cet ID existe dans la table `categorie_articles`
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'num_article' => 'ART002',
                'nom_article' => 'Consultation Informatique',
                'description' => 'Service de consultation informatique pour entreprises.',
                'prix_unitaire' => 100.00,
                'quantite' => 4,
                'benefice' => null,
                'prix_achat' => null,
                'prix_promo' => null,
                'prix_tva' => 120.00,
                'doc_externe' => 'consultation_terms.pdf',
                'tva' => 20.00,
                'benefice_promo' => null,
                'quantite_alert' => null,
                'type_article' => 'service',
                'unité' => 'h',
                'promo_id' => 1, // Assurez-vous que cet ID existe dans la table `promos`
                'id_comptable' => 2, // Assurez-vous que cet ID existe dans la table `compte_comptables`
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 2, // Assurez-vous que cet ID existe dans la table `users`
                'id_categorie_article' => 2, // Assurez-vous que cet ID existe dans la table `categorie_articles`
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
