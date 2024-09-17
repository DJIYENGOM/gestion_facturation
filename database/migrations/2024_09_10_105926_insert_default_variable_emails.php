<?php

use App\Models\VariableEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class InsertDefaultVariableEmails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Liste des catégories de dépenses par défaut
        $defaultVariable = [
            'nom_entreprise',
            'site_web',
            'nom_destinataire',
            'liste_produit',
            'vente_numero',
            'date_vente',
            'prix_total_vente',
            'num_paiement',
            'date_paiement',
            'montant_paiement',
            'date_echeance',
            'montant_echeance',
            'numero_devi',
            'date_devi',
            'montant_devi',
            'numero_commande_vente',
            'date_commande_vente',
            'montant_commande_vente',
            'numero_livraison',
            'date_livraison',
            'montant_livraison',
            'numero_commande_achat',
            'date_commande_achat',
            'montant_commande_achat'
        ];

        foreach ($defaultVariable as $variable) {
            VariableEmail::create([
                'nom_variable' => $variable,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('variable_emails')->whereIn('nom_variable', [
            'nom_entreprise',
            'site_web',
            'nom_destinataire',
            'liste_produit',
            'vente_numero',
            'date_vente',
            'prix_total_vente',
            'num_paiement',
            'date_paiement',
            'montant_paiement',
            'date_echeance',
            'montant_echeance',
            'numero_devi',
            'date_devi',
            'montant_devi',
            'numero_commande_vente',
            'date_commande_vente',
            'montant_commande_vente',
            'numero_livraison',
            'date_livraison',
            'montant_livraison',
            'numero_commande_achat',
            'date_commande_achat',
            'montant_commande_achat'
        ])->delete();
    }
}
