<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    protected $fillable = [
        'num_depense',
        'activation',
        'id_categorie_depense',
        'commentaire',
        'date_paiement',
        'tva_depense',
        'montant_depense_ht',
        'montant_depense_ttc',
        'plusieurs_paiement',
        'duree_indeterminee',
        'periode_echeance',
        'nombre_periode',
        'doc_externe',
        'statut_depense',
        'fournisseur_id',
        'num_facture',
        'date_facture',
        'statut_paiement',
        'id_paiement',
        'id_compte_comptable',
        'sousUtilisateur_id',
        'user_id',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'fournisseur_id');
    }

    public function paiement()
    {
        return $this->belongsTo(Payement::class, 'id_paiement');
    }

    public function categorieDepense()
    {
        return $this->belongsTo(CategorieDepense::class, 'id_categorie_depense');
    }

    public function compteComptable()
    {
        return $this->belongsTo(CompteComptable::class, 'id_compte_comptable');
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

