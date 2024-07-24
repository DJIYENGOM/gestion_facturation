<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_commande',
        'date_commande', 
        'date_limite_commande',
        'titre',
        'description', 
        'prix_HT',
        'prix_TTC',
        'note_commande',
        'reduction_commande',
        'active_Stock',
        'statut_commande',
        'archiver',
        'client_id',
        'sousUtilisateur_id',
        'user_id',
        'id_comptable',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArticleBonCommande::class, 'id_BonCommande');
    }
    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function echeances()
    {
        return $this->hasMany(Echeance::class, 'bonCommande_id');
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }


   

}
