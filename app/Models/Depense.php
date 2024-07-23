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
        'categorie_depense_id',
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
}

