<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'article_id',
        'depense_id',
        'facture_id',
        'echeance_id',
        'devis_id',
        'sousUtilisateur_id',
        'user_id',
    ];
}
