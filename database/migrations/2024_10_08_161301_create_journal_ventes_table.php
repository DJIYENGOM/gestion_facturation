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
        Schema::create('journal_ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_facture')->nullable()->constrained('factures')->onDelete('cascade');
            $table->foreignId('id_factureAvoir')->nullable()->constrained('facture_avoirs')->onDelete('cascade');
            $table->foreignId('id_article')->nullable()->constrained('artcle_factures')->onDelete('cascade');
            $table->foreignId('id_compte_comptable')->nullable()->constrained('compte_comptables')->onDelete('cascade');
            $table->foreignId('id_depense')->nullable()->constrained('depenses')->onDelete('cascade');
            $table->decimal('debit', 10, 2)->nullable();
            $table->decimal('credit', 10, 2)->nullable();
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
        Schema::dropIfExists('journal_ventes');
    }
};
