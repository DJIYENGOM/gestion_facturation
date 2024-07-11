<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureAvoir extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_factureAvoir',
        'titre',
        'description',
        'client_id',
        'facture_id',
        'date',
        'prix_HT',
        'prix_TTC',
        'active_Stock',
        'commentaire',
        'doc_externe',
        'sousUtilisateur_id',
        'user_id',
    ];
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'facture_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function articles()
    {
        return $this->hasMany(ArticleFactureAvoir::class, 'id_FactureAvoir');
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
