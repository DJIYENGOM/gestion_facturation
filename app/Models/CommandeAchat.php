<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeAchat extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_commandeAchat',
        'activation',
        'date_commandeAchat',
        'date_livraison',
        'titre',
        'description',
        'date_paiement',
        'statut_commande',
        'fournisseur_id',
        'total_TTC',
        'active_Stock',
        'depense_id',
        'commentaire',
        'note_interne',
        'doc_interne',
        'sousUtilisateur_id',
        'user_id',
    ];

    


    public function articles()
    {
        return $this->hasMany(ArticleCommandeAchat::class, 'id_CommandeAchat');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function depense()
    {
        return $this->belongsTo(Depense::class, 'depense_id');
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
