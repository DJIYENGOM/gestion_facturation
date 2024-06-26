<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            [
            'name' => 'Admin User',
            'password' => Hash::make('password'), // Remplacez 'password' par un mot de passe sécurisé
            'email' => 'admin@example.com',
            'role' => 'super_admin',
            'nom_entreprise' => 'AdminCorp',
            'description_entreprise' => 'Administration and Management Services',
            'logo' => 'admincorp_logo.png',
            'adress_entreprise' => '123 Admin Street',
            'tel_entreprise' => '123456789',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'name' => 'Regular User',
            'password' => Hash::make('password'), // Remplacez 'password' par un mot de passe sécurisé
            'email' => 'user@example.com',
            'role' => 'user', // Rôle différent pour montrer la variété des utilisateurs
            'nom_entreprise' => 'UserCompany',
            'description_entreprise' => 'Standard User Company',
            'logo' => 'usercompany_logo.png',
            'adress_entreprise' => '456 User Avenue',
            'tel_entreprise' => '987654321',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]
       ]);
    }
}
