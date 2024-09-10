<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiquette extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_etiquette',
        'code_etiquette',
       
        'user_id',
        'sousUtilisateur_id',
    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function articlesEtiquette()
    {
        return $this->hasMany(Article_Etiquette::class);
    }
}
