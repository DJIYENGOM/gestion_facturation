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
        Schema::create('sous__utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->enum('archiver', ['oui', 'non'])->default('non');
            $table->enum('role', ['administrateur', 'utilisateur_simple'])->default('utilisateur_simple');
            $table->boolean('visibilite_globale')->default('1');
            $table->boolean('fonction_admin')->default('1');
            $table->boolean('acces_rapport')->default('1');
            $table->boolean('gestion_stock')->default('1');
            $table->boolean('commande_achat')->default('1');
            $table->boolean('export_excel')->default('1');
            $table->boolean('supprimer_donnees')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sous__utilisateurs');
    }
};
