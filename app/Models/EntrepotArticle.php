<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntrepotArticle extends Model
{
    use HasFactory;

    protected $fillable = ['article_id', 'entrepot_id', 'quantiteArt_entrepot'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function entrepot()
    {
        return $this->belongsTo(Entrepot::class, 'entrepot_id');
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

