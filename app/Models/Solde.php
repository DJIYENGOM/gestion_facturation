<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solde extends Model
{
    use HasFactory;

    protected $fillable = ['date_paiement','montant','commentaire','id_paiement','client_id','facture_id', 'sousUtilisateur_id','user_id'];

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
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
    }
}
