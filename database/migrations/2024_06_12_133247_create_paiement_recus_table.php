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
        Schema::create('paiement_recus', function (Blueprint $table) {
            $table->id();
            $table->date('date_prevu')->nullable();
            $table->string('num_paiement')->nullable()->unique();
            $table->date('date_recu');
            $table->decimal('montant', 10, 2)->default(0);
            $table->text('commentaire')->nullable();
            $table->foreignId('id_paiement')->nullable()->constrained('payements')->onDelete('set null');
            $table->foreignId('facture_id')->constrained('factures')->onDelete('cascade');
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
        Schema::dropIfExists('paiement_recus');
    }
};
