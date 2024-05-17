<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_categorie','sousUtilisateur_id','user_id'
    ];

    public function sousUtilisateurs()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'categorie_id');
    }
}
