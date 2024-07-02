<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureAccompt extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'titreAccomp',
        'dateAccompt',
        'dateEcheance',
        'montant',
        'commentaire',
        'devi_id',
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

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
