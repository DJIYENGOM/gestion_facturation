<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_facture',
        'date_creation', 
        'type_facture',
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
        'id_recurrent',
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

    public function factureRcurrente()
    {
        return $this->belongsTo(FactureRecurrente::class, 'id_recurrent');
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

    public function Etiquettes()
    {
        return $this->hasMany(Facture_Etiquette::class, 'facture_id');
    }

}
