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
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->string('num_depense')->nullable();
            $table->text('commentaire')->nullable();
            $table->date('date_paiement')->nullable();
            $table->integer('tva_depense')->nullable();
            $table->decimal('montant_depense_ht', 10, 2)->nullable()->default(0);
            $table->decimal('montant_depense_ttc', 10, 2)->nullable()->default(0);
            $table->boolean('plusieurs_paiement')->default(false);
            $table->boolean('duree_indeterminee')->default(false);
            $table->enum('periode_echeance', ['jour', 'mois', 'semaine'])->nullable();
            $table->integer('nombre_periode')->nullable();
            $table->string('doc_externe')->nullable();
            $table->string('num_facture')->nullable();
            $table->date('date_facture')->nullable();
            $table->string('image_facture')->nullable();
            $table->enum('statut_depense', ['payer', 'impayer'])->default('impayer');
            $table->foreignId('id_paiement')->nullable()->constrained('payements')->onDelete('set null');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->foreignId('id_categorie_depense')->nullable()->constrained('categorie_depenses')->onDelete('set null');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
