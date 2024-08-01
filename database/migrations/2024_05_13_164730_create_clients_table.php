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
            $table->string('num_client')->nullable();
            $table->string('nom_client')->nullable();
            $table->string('prenom_client')->nullable();
            $table->string('nom_entreprise')->nullable();
            $table->string('adress_client')->nullable();
            $table->string('email_client')->nullable();
            $table->string('tel_client');
            $table->enum('type_client', ['particulier', 'entreprise']);
            $table->enum('statut_client', ['client', 'prospect']);
            $table->string('num_id_fiscal')->nullable();
            $table->string('code_postal_client')->nullable();
            $table->string('ville_client')->nullable();
            $table->string('pays_client')->nullable();
            $table->string('noteInterne_client')->nullable();
            $table->string('nom_destinataire')->nullable();
            $table->string('pays_livraison')->nullable();
            $table->string('ville_livraison')->nullable();
            $table->string('code_postal_livraison')->nullable();
            $table->string('tel_destinataire')->nullable();
            $table->string('email_destinataire')->nullable();
            $table->string('infoSupplemnt')->nullable();

            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('categorie_id')->nullable()->constrained('categorie_clients')->onDelete('set null');
            $table->foreignId('id_comptable')->nullable()->constrained('compte_comptables')->onDelete('set null');

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
