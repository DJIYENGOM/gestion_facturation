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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->boolean('produit_rupture')->default(true);
            $table->boolean('depense_impayer')->default(true);
            $table->boolean('payement_attente')->default(true);
            $table->boolean('devis_expirer')->default(true);
            $table->boolean('relance_automatique')->default(true);

            $table->integer('quantite_produit')->default(5);
            $table->integer('nombre_jourNotif_brouillon')->default(7);
            $table->integer('nombre_jourNotif_depense')->default(7);
            $table->integer('nombre_jourNotif_echeance')->default(7);
            $table->integer('nombre_jourNotif_devi')->default(7);
            $table->boolean('recevoir_notification')->default(true);
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
        Schema::dropIfExists('notifications');
    }
};
