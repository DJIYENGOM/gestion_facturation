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
        Schema::create('commande_achats', function (Blueprint $table) {
            $table->id();
            $table->string('num_commandeAchat')->nullable();
            $table->date('date_commandeAchat')->nullable();
            $table->date('date_livraison')->nullable();
            $table->string('titre')->nullable();
            $table->string('description')->nullable();
            $table->date('date_paiement')->nullable();
            $table->enum('statut_commande' , ['commander','annuler','recu','brouillon'])->default('brouillon');
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->onDelete('set null');
            $table->decimal('total_TTC', 10, 2)->nullable();
            $table->boolean('active_Stock')->default(0);
            $table->foreignId('depense_id')->nullable()->constrained('depenses')->onDelete('set null');
            $table->string('commentaire')->nullable();
            $table->string('note_interne')->nullable();
            $table->string('doc_interne')->nullable();
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
        Schema::dropIfExists('commande_achats');
    }
};
