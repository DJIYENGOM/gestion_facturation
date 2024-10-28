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
        Schema::create('echeances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('cascade');
            $table->foreignId('devi_id')->nullable()->constrained('devis')->onDelete('cascade');
            $table->foreignId('bonCommande_id')->nullable()->constrained('bon_commandes')->onDelete('cascade'); 
            $table->foreignId('id_depense')->nullable()->constrained('depenses')->onDelete('set null');
            $table->date('date_pay_echeance')->nullable();
            $table->decimal('montant_echeance', 8, 2)->nullable()->default(0);
            $table->text('commentaire')->nullable();
            $table->enum('statut_paiement', ['payer', 'nonpayer'])->default('nonpayer');
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
        Schema::dropIfExists('echeances');
    }
};
