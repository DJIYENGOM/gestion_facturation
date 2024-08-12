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
        Schema::create('article_commande_achats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_CommandeAchat')->constrained('commande_achats')->onDelete('cascade');
            $table->foreignId('id_article')->constrained('articles')->onDelete('cascade');
            $table->decimal('reduction_article', 10, 2)->nullable();
            $table->decimal('TVA_article', 5, 2)->nullable();
            $table->decimal('prix_unitaire_article', 10, 2)->nullable();
            $table->integer('quantite_article')->nullable(); 
            $table->decimal('prix_total_article', 10, 2)->nullable();
            $table->decimal('prix_total_tva_article', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_commande_achats');
    }
};
