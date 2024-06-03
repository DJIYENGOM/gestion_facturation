<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrepot extends Model
{
    use HasFactory;

    protected $fillable = ['nomEntrepot', 'sousUtilisateur_id', 'user_id'];

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'entrepot_articles')->withPivot('quantiteArt_entrepot')->withTimestamps();
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

