<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_stock',
        'num_stock',
        'libelle',
        'disponible_avant',
        'modif',
        'disponible_apres',
        'quantite_ajoutee',
        'article_id',
        'facture_id',
        'bonCommande_id',
        'livraison_id',
        'statut_stock',
        'type_stock',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }   

    public function bonCommande()
    {
        return $this->belongsTo(BonCommande::class);
    }

    public function livraison()
    {
        return $this->belongsTo(Livraison::class);
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
