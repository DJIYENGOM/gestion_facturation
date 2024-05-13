<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_categorie','sousUtilisateur_id'
    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class);
    }
}
