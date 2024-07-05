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
        Schema::create('numero_configurations', function (Blueprint $table) {
            $table->id();
            $table->enum('type_document', ['facture', 'livraison', 'produit','service', 'client', 'devis','commande','depense','fournisseur','commande_achat']);
            $table->enum('type_numerotation', ['par_defaut', 'avec_prefixe']);
            $table->string('prefixe')->nullable();
            $table->enum('format', ['annee', 'annee_mois', 'annee_mois_jour'])->nullable();
            $table->integer('compteur')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numero_configurations');
    }
};
