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
        Schema::create('facture__etiquettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etiquette_id')->nullable()->constrained('etiquettes')->onDelete('set null');
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('set null');
            $table->foreignId('devi_id')->nullable()->constrained('devis')->onDelete('set null');
            $table->foreignId('bonCommande_id')->nullable()->constrained('bon_commandes')->onDelete('set null');
            $table->foreignId('commandeAchat_id')->nullable()->constrained('commande_achats')->onDelete('set null');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->foreignId('livraison_id')->nullable()->constrained('livraisons')->onDelete('set null');
            $table->foreignId('depense_id')->nullable()->constrained('depenses')->onDelete('set null');
            $table->foreignId('factureAvoir_id')->nullable()->constrained('facture_avoirs')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture__etiquettes');
    }
};
