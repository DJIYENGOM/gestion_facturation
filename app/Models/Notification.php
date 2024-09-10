<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_rupture',
        'depense_impayer',
        'payement_attente',
        'devis_expirer',
        'relance_automatique',

        'sousUtilisateur_id', 
        'user_id',
        'quantite_produit',
        'nombre_jourNotif_brouillon',
        'nombre_jourNotif_depense',
        'nombre_jourNotif_echeance',
        'nombre_jourNotif_devi',
        'recevoir_notification',

    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
