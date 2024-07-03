<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_fact',
        'date_creation', 
        'reduction_facture', 
        'prix_HT',
        'prix_TTC',
        'note_fact',
        'date_paiement',
        'archiver',
        'active_Stock',
        'statut_paiement',
        'client_id',
        'sousUtilisateur_id',
        'user_id',
        'type_paiement',
        'id_paiement',
        'id_comptable',

        'devi_id',
        'bonCommande_id',
    ];

 

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relation avec les articles de la facture
    public function articles()
    {
        return $this->hasMany(ArtcleFacture::class, 'id_facture');
    }

    public function devi()
    {
        return $this->belongsTo(Devi::class, 'devi_id');
    }

    public function bonCommande()
    {
        return $this->belongsTo(BonCommande::class, 'bonCommande_id');
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


    public static function generateNumFacture($id)
    {
        $year = date('Y');
        return 'F' . $year . '00' . $id;
    }
}
