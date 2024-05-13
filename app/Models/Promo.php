<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = ['nom_promo', 'pourcentage_promo', 'date_expiration', 'sousUtilisateur_id'];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
