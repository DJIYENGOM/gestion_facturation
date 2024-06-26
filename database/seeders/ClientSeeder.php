<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run()
    {
        DB::table('clients')->insert([
            [
                'num_client' => 'CL001',
                'nom_client' => 'Doe',
                'prenom_client' => 'John',
                'nom_entreprise' => 'JD Enterprises',
                'adress_client' => '123 Main St',
                'email_client' => 'john.doe@example.com',
                'tel_client' => '123-456-7890',
                'type_client' => 'entreprise',
                'statut_client' => 'client',
                'num_id_fiscal' => 'FI123456789',
                'code_postal_client' => '12345',
                'ville_client' => 'Metropolis',
                'pays_client' => 'France',
                'noteInterne_client' => 'Preferred client',
                'nom_destinataire' => 'John Doe',
                'pays_livraison' => 'France',
                'ville_livraison' => 'Metropolis',
                'code_postal_livraison' => '12345',
                'tel_destinataire' => '123-456-7890',
                'email_destinataire' => 'delivery.john.doe@example.com',
                'infoSupplemnt' => 'None',
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 1, // Assurez-vous que cet ID existe dans la table `users`
                'categorie_id' => 1, // Assurez-vous que cet ID existe dans la table `categorie_clients`
                'id_comptable' => null, // Assurez-vous que cet ID existe dans la table `compte_comptables`
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'num_client' => 'CL002',
                'nom_client' => 'Smith',
                'prenom_client' => 'Jane',
                'nom_entreprise' => 'JS Solutions',
                'adress_client' => '456 Elm St',
                'email_client' => 'jane.smith@example.com',
                'tel_client' => '987-654-3210',
                'type_client' => 'particulier',
                'statut_client' => 'prospect',
                'num_id_fiscal' => null,
                'code_postal_client' => '67890',
                'ville_client' => 'Gotham',
                'pays_client' => 'USA',
                'noteInterne_client' => 'Looking for services',
                'nom_destinataire' => 'Jane Smith',
                'pays_livraison' => 'USA',
                'ville_livraison' => 'Gotham',
                'code_postal_livraison' => '67890',
                'tel_destinataire' => '987-654-3210',
                'email_destinataire' => 'delivery.jane.smith@example.com',
                'infoSupplemnt' => 'Needs additional information',
                'sousUtilisateur_id' => null, // Assurez-vous que cet ID existe dans la table `sous__utilisateurs`
                'user_id' => 2, // Assurez-vous que cet ID existe dans la table `users`
                'categorie_id' => 1, // Assurez-vous que cet ID existe dans la table `categorie_clients`
                'id_comptable' => null, // Assurez-vous que cet ID existe dans la table `compte_comptables`
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
