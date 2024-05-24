<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_fact',
        'date_creation', 
        'reduction_facture', 
        'montant_total_fact',
        'note_fact',
        'archiver',
        'client_id',
        'sousUtilisateur_id',
        'user_id'
    ];
}
