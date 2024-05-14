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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom_client');
            $table->string('prenom_client');
            $table->string('nom_entreprise');
            $table->string('adress_client');
            $table->string('email_client');
            $table->string('tel_client');
            $table->foreignId('sousUtilisateur_id')->constrained('sous__utilisateurs')->onDelete('cascade');
            $table->foreignId('categorie_id')->constrained('categorie_clients')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
