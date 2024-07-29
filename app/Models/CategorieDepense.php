<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieDepense extends Model
{
    use HasFactory;

    protected $fillable = ['nom_categorie_depense', 'user_id', 'sousUtilisateur_id'];

    public function sousUtilisateurs()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class, 'id_categorie_depense');
    }
}
