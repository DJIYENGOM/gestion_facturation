<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrilleTarifaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'montantTarif',
        'tva',
        'montantTva',
        'idArticle',
        'idClient',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'idArticle');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'idClient');
    }

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
