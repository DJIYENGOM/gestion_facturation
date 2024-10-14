<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalVente extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_facture',
        'id_article',
        'id_factureAvoir',
        'id_compte_comptable',
        'id_depense',
        'debit',
        'credit',
        'user_id',
        'sousUtilisateur_id',
    ];

    public function articles()
    {
        return $this->belongsTo(ArtcleFacture::class, 'id_article');
    }
    public function facture()
    {
        return $this->belongsTo(Facture::class, 'id_facture');
    }

    public function factureAvoir()
    {
        return $this->belongsTo(FactureAvoir::class, 'id_factureAvoir');
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_compte_comptable');
    }

    public function depense()
    {
        return $this->belongsTo(Depense::class, 'id_depense');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }
}
