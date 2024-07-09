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
        Schema::create('facture_recurrentes', function (Blueprint $table) {
            $table->id();
            $table->string('num_factureRec')->nullable();
            $table->enum('periode', ['mois', 'semaine', 'jour']);
            $table->integer('nombre_periode');
            $table->boolean('etat_brouillon');
            $table->boolean('envoyer_mail');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->decimal('prix_HT')->nullable();
            $table->decimal('prix_TTC')->nullable();
            $table->enum('active_Stock', ['non', 'oui'])->default('non');
            $table->string('commentaire');
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
        Schema::dropIfExists('facture_recurrentes');
    }
};
