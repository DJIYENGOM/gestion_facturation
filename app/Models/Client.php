<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom_client',
        'prenom_client',
        'nom_entreprise',
        'email_client',
        'adress_client',
        'tel_client',
        'sousUtilisateur_id',
        'categorie_id'
    ];
}
