<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_fournisseur',
        'nom_fournisseur',
        'prenom_fournisseur',
        'nom_entreprise',
        'email_fournisseur',
        'adress_fournisseur',
        'tel_fournisseur',
        'sousUtilisateur_id',
        'user_id',
        'num_id_fiscal',
        'type_fournisseur',
        'code_postal_fournisseur',
        'ville_fournisseur',
        'pays_fournisseur',
        'noteInterne_fournisseur',
        'doc_associer',
        'id_comptable',
        'code_banque',
        'code_guichet',
        'num_compte',
        'cle_rib',
        'iban',
    ];

  

    public function sousUtilisateur()
    {
        return $this->belongsTo(Sous_Utilisateur::class, 'sousUtilisateur_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class, 'fournisseur_id');
    }

    public function Etiquettes()
    {
        return $this->hasMany(Facture_Etiquette::class, 'fournisseur_id');
    }
}
