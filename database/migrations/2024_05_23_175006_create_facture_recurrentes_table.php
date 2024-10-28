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
            $table->enum('periode', ['mois', 'semaine', 'jour']);
            $table->integer('nombre_periode');
            $table->date('date_debut');
            $table->enum('type_reccurente', ['creer_brouillon', 'envoyer_email']);
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('cascade');
            $table->boolean('creation_automatique')->default(1);
            $table->decimal('prix_HT', 10, 2)->nullable()->default(0);
            $table->decimal('prix_TTC', 10, 2)->nullable()->default(0);
            $table->enum('active_Stock', ['non', 'oui'])->default('non');
            $table->string('commentaire')->nullable();
            $table->string('note_interne')->nullable();
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
