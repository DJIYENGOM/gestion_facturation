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
        Schema::create('historiques', function (Blueprint $table) {
            $table->id();
            $table->string('message');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_facture')->nullable()->constrained('factures')->onDelete('set null');
            $table->foreignId('id_commandeAchat')->nullable()->constrained('commande_achats')->onDelete('set null');
            $table->foreignId('id_depense')->nullable()->constrained('depenses')->onDelete('set null');
            $table->foreignId('id_bonCommande')->nullable()->constrained('bon_commandes')->onDelete('set null');
            $table->foreignId('id_livraison')->nullable()->constrained('livraisons')->onDelete('set null');
            $table->foreignId('id_fournisseur')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->foreignId('id_article')->nullable()->constrained('articles')->onDelete('set null');
            $table->foreignId('id_facture_avoir')->nullable()->constrained('facture_avoirs')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historiques');
    }
};
