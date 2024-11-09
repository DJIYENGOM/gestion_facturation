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

     // Automatically set num_paiement
     public static function boot()
     {
         parent::boot();
 
         static::creating(function ($paiementRecu) {
             // Find the latest `num_paiement` for this `facture_id`
             $lastPayment = self::where('facture_id', $paiementRecu->facture_id)
                 ->orderBy('num_paiement', 'desc')
                 ->first();
 
             // Increment the number or start at 01
             if ($lastPayment) {
                 $lastNum = intval($lastPayment->num_paiement);
                 $paiementRecu->num_paiement = str_pad($lastNum + 1, 2, '0', STR_PAD_LEFT);
             } else {
                 $paiementRecu->num_paiement = '01';
             }
         });
     }
}
