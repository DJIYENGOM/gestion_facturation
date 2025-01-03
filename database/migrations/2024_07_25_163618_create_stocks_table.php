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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->date('date_stock');
            $table->string('num_stock');
            $table->string('libelle');
            $table->integer('disponible_avant')->nullable();
            $table->integer('modif')->nullable();
            $table->integer('disponible_apres')->nullable();
            $table->integer('quantite_ajoutee')->nullable()->default(0);
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('set null');
            $table->foreignId('factureAvoir_id')->nullable()->constrained('facture_avoirs')->onDelete('set null');
            $table->foreignId('commandeAchat_id')->nullable()->constrained('commande_achats')->onDelete('set null');
            $table->foreignId('bonCommande_id')->nullable()->constrained('bon_commandes')->onDelete('set null');
            $table->foreignId('livraison_id')->nullable()->constrained('livraisons')->onDelete('set null');
            $table->string('statut_stock')->nullable();
            $table->enum('type_stock', ['entree', 'sortie'])->nullable();
            
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
        Schema::dropIfExists('stocks');
    }
};
