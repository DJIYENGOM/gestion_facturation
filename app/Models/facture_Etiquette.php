<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class facture_Etiquette extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'devi_id',
        'bonCommande_id',
        'commandeAchat_id',
        'fournisseur_id',
        'livraison_id',
        'depense_id',
        'factureAvoir_id',
        'etiquette_id',
    ];
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function etiquette()
    {
        return $this->belongsTo(Etiquette::class, 'etiquette_id');
    }
}
