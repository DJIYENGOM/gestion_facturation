<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['nom_article', 'description', 'prix_unitaire', 'type_article','prix_promo', 'promo_id', 'sousUtilisateur_id'];

    public function promotion()
    {
        return $this->belongsTo(Promo::class);
    }


    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class);
    }
}
