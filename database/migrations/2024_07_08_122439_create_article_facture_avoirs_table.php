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
        Schema::create('article_facture_avoirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_factureAvoir')->constrained('facture_avoirs')->onDelete('cascade');
            $table->foreignId('id_article')->constrained('articles')->onDelete('cascade');
            $table->decimal('reduction_article')->nullable();
            $table->decimal('TVA_article')->nullable();
            $table->decimal('prix_unitaire_article')->nullable();
            $table->integer('quantite_article')->nullable(); 
            $table->decimal('prix_total_article')->nullable();
            $table->decimal('prix_total_tva_article')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_facture_avoirs');
    }
};
