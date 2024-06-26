<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_categorie_article','sousUtilisateur_id','user_id','type_categorie_article'
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
        return $this->hasMany(Article::class, 'id_categorie_article');
    }
}
