<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_modele_id', 'chemin_fichier'
    ];

    public function emailModele()
    {
        return $this->belongsTo(EmailModele::class, 'email_modele_id');
    }
}
