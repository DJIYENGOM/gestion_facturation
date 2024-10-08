<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payement extends Model
{
    use HasFactory;

    protected $fillable = ['nom_payement', 'sousUtilisateur_id','user_id'];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function facture()
    {
        return $this->hasMany(Facture::class,'id_paiement');
    }

    public function depense()
    {
        return $this->hasMany(Depense::class,'id_paiement');
    }
}   
