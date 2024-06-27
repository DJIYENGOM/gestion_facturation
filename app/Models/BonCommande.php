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
        'prix_HT',
        'prix_TTC',
        'note_commande',
        'reduction_commande',
        'statut_commande',
        'archiver',
        'client_id',
        'sousUtilisateur_id',
        'user_id',
        'id_comptable',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArtcleFacture::class);
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
        return $this->hasMany(Echeance::class);
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }


    public static function generateNumBoncommande($id)
    {
        $year = date('Y');
        return 'BC' . $year . '00' . $id;
    }

}
