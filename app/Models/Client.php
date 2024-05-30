<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'nom_client',
        'prenom_client',
        'nom_entreprise',
        'email_client',
        'adress_client',
        'tel_client',
        'sousUtilisateur_id',
        'categorie_id',
        'user_id',
        'num_id_fiscal',
        'type_client',
        'statut_client',
        'code_postal_client',
        'ville_client',
        'pays_client',
        'noteInterne_client',
        'nom_destinataire',
        'pays_livraison',
        'ville_livraison',
        'code_postal_livraison',
        'tel_destinataire',
        'email_destinataire',
        'infoSupplemnt'
    ];

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieClient::class, 'categorie_id');
    }
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
