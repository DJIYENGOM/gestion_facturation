<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_modeles', function (Blueprint $table) {
            $table->id();
            $table->enum('type_modele', ['facture', 'devi', 'resumer_vente', 'recu_paiement',
             'relanceAvant_echeance', 'relanceApres_echeance', 'commande_vente', 'livraison', 'fournisseur'])->unique();
            $table->string('object');
            $table->text('contenu');
            $table->foreignId('id_variable')->nullable()->constrained('variable_emails')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');

            $table->timestamps();
            $table->unique('type_modele');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_modeles');
    }
};
