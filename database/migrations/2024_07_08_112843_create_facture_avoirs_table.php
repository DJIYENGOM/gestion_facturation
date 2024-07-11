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
        Schema::create('facture_avoirs', function (Blueprint $table) {
            $table->id();
            $table->string('num_factureAvoir');
            $table->string('titre')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('set null');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->date('date');
            $table->decimal('prix_HT')->nullable();
            $table->decimal('prix_TTC')->nullable();
            $table->enum('active_Stock', ['non', 'oui'])->default('oui');
            $table->string('commentaire')->nullable();
            $table->string('doc_externe')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facture_avoirs');
    }
};
