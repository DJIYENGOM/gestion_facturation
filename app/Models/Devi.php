<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devi extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'num_devi',
        'date_devi', 
        'date_limite', 
        'prix_HT',
        'prix_TTC',
        'note_devi',
        'reduction_devi',
        'statut_devi',
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

    public function factureAccompts()
    {
        return $this->hasMany(FactureAccompt::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
    }
    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_comptable');
    }


    public static function generateNumdevi($id)
    {
        $year = date('Y');
        return 'D' . $year . '00' . $id;
    }
}
