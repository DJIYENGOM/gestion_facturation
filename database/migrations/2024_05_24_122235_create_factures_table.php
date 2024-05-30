<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('num_fact')->unique()->nullable();
            $table->date('date_creation');
            $table->decimal('reduction_facture')->nullable();
            $table->decimal('montant_total_fact');
            $table->text('note_fact')->nullable();
            $table->enum('validation', ['non', 'oui'])->default('non');
            $table->enum('statut', ['payer', 'non_payer','en_attente']);
            $table->enum('archiver', ['oui', 'non'])->default('non');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
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
        Schema::dropIfExists('factures');
    }
};
