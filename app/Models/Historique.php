<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historique extends Model
{
    use HasFactory;

    protected $fillable = [
        'message', 
        'sousUtilisateur_id', 
        'user_id',
        'id_facture',
        'id_devis',
        'id_commandeAchat',
        'id_depense',
        'id_bonCommande',
        'id_livraison',
        'id_fournisseur',
        'id_article',
        'id_facture_avoir'


];

public function sousUtilisateur()
{
    return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
}

public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

}
