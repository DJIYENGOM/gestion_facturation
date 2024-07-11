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
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('num_fournisseur')->nullable();
            $table->string('nom_fournisseur')->nullable();
            $table->string('prenom_fournisseur')->nullable();
            $table->string('nom_entreprise')->nullable();
            $table->string('adress_fournisseur')->nullable();
            $table->string('email_fournisseur')->nullable();
            $table->string('tel_fournisseur');
            $table->string('num_id_fiscal')->nullable();
            $table->string('code_postal_fournisseur')->nullable();
            $table->string('ville_fournisseur')->nullable();
            $table->string('pays_fournisseur')->nullable();
            $table->enum('type_fournisseur',['particulier', 'entreprise'])->default('particulier');
            $table->string('noteInterne_fournisseur')->nullable();
            $table->string('doc_associer')->nullable();

            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');            
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_comptable')->nullable()->constrained('compte_comptables')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
