<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class SousUtilisateurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sous__utilisateurs')->insert([
            [
            'nom' => 'SousUser1',
            'prenom' => 'UserPrenom1',
            'email' => 'soususer@example1.com',
            'password' => Hash::make('userpassword'), // Remplacez 'userpassword' par un mot de passe sécurisé
            'id_user' => 1, // Assurez-vous que cet utilisateur existe dans la table `users`
            'archiver' => 'non',
            'role' => 'utilisateur_simple',
            'created_at' => now(),
            'updated_at' => now(),
        ],

        [
            'nom' => 'SousUser2',
            'prenom' => 'UserPrenom2',
            'email' => 'soususer@example2.com',
            'password' => Hash::make('userpassword'), // Remplacez 'userpassword' par un mot de passe sécurisé
            'id_user' => 2, // Assurez-vous que cet utilisateur existe dans la table `users`
            'archiver' => 'non',
            'role' => 'utilisateur_simple',
            'created_at' => now(),
            'updated_at' => now(),
        ]
        ]);
    }
}
