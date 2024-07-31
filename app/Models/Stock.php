<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_stock',
        'num_stock',
        'libelle',
        'disponible_avant',
        'modif',
        'disponible_apres',
        'article_id',
        'facture_id',
        'bonCommande_id',
        'livraison_id',
        'sousUtilisateur_id',
        'user_id',
    ];
}
