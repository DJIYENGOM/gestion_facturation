<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigurationRelanceAuto extends Model
{
    use HasFactory;

    protected $fillable = [
        'envoyer_rappel_avant',
        'nombre_jour_avant',
        'envoyer_rappel_apres',
        'nombre_jour_apres',
        'user_id',
        'sousUtilisateur_id'
    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
