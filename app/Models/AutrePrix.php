<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutrePrix extends Model
{
    use HasFactory;

    protected $table = 'autre_prix'; // Spécifiez explicitement la table

    protected $fillable = ['article_id', 'titrePrix', 'montant', 'tva','montantTva'];

    public function article()
    {
        return $this->belongsTo(Article::class);
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

