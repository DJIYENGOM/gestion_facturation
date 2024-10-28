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
        Schema::create('article_facture_recurrentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_factureRec')->constrained('facture_recurrentes')->onDelete('cascade');
            $table->foreignId('id_article')->constrained('articles')->onDelete('cascade');
            $table->decimal('reduction_article', 10, 2)->nullable();
            $table->decimal('TVA_article')->nullable()->default(0);
            $table->decimal('prix_unitaire_article', 10, 2)->nullable()->default(0);
            $table->integer('quantite_article')->nullable(); 
            $table->decimal('prix_total_article', 10, 2)->nullable()->default(0);
            $table->decimal('prix_total_tva_article', 10, 2)->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_facture_recurrentes');
    }
};
