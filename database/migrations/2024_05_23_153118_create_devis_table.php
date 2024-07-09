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
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('num_devi')->nullable();
            $table->date('date_devi');
            $table->date('date_limite')->nullable();
            $table->decimal('prix_HT')->nullable();
            $table->decimal('prix_TTC')->nullable();
            $table->text('note_devi')->nullable();
            $table->decimal('reduction_devi')->nullable();
            $table->enum('statut_devi',['en_attente','transformer','valider', 'annuler','brouillon'])->default('brouillon');
            $table->enum('archiver', ['oui', 'non'])->default('non');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('cascade');
            $table->foreignId('id_comptable')->nullable()->constrained('compte_comptables')->onDelete('set null');
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
        Schema::dropIfExists('devis');
    }
};
