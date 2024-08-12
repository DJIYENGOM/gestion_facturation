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
        Schema::create('facture_accompts', function (Blueprint $table) {
            $table->id();
            $table->string('num_factureAccomp')->nullable();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('cascade'); 
            $table->foreignId('devi_id')->nullable()->constrained('devis')->onDelete('cascade'); 
            $table->string('num_devi')->nullable();
            $table->string('num_facture')->nullable();
            $table->string('titreAccomp')->nullable();
            $table->date('dateAccompt')->nullable(); 
            $table->date('dateEcheance')->nullable(); 
            $table->decimal('montant', 10, 2)->nullable(); 
            $table->text('commentaire')->nullable();
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
        Schema::dropIfExists('facture_accompts');
    }
};
