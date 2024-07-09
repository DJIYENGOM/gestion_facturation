<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureRecurrente extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_factureRec',
        'periode',
        'nombre_periode',
        'etat_brouillon',
        'envoyer_mail',
        'client_id',
        'prix_HT',
        'prix_TTC',
        'active_Stock',
        'commentaire',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function articles()
    {
        return $this->hasMany(ArticleFactureAvoir::class, 'id_FactureAvoir');
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
