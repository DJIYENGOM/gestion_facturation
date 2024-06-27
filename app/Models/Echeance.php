<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Echeance extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'date_pay_echeance',
        'montant_echeance',
        'statut_paiement',
        'commentaire',
        'devi_id',
        'id_BonCommande',
        'sousUtilisateur_id',
        'user_id',

    ];

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function devi()
    {
        return $this->belongsTo(Devi::class, 'devi_id');
    }

    public function BonCommande()
    {
        return $this->belongsTo(BonCommande::class, 'id_BonCommande');
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
