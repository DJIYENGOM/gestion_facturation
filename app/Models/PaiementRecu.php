<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementRecu extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'num_paiement',
        'date_prevu', 
        'date_recu', 
        'montant', 
        'commentaire',
        'id_paiement', 
        'sousUtilisateur_id', 'user_id'];

  
    
    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
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
