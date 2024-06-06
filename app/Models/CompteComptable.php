<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteComptable extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_compte_comptable',
        'code_compte_comptable',
        'user_id',
        'sousUtilisateur_id',
    ];

    public function sousUtilisateurs()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'id_comptable');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'id_comptable');
    }
}
