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
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->string('num_livraison')->nullable()->unique();
            $table->date('date_livraison');
            $table->decimal('prix_HT');
            $table->decimal('prix_TTC');
            $table->string('note_livraison')->nullable();
            $table->decimal('reduction_livraison')->nullable();
            $table->enum('active_Stock', ['non', 'oui'])->default('oui');
            $table->enum('statut_livraison', ['brouillon', 'preparer', 'planifier','livrer','annuler'])->default('brouillon');
            $table->enum('archiver', ['oui', 'non'])->default('non');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('cascade');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
