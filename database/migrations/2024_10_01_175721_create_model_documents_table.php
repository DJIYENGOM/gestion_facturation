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
        Schema::create('model_documents', function (Blueprint $table) {
            $table->id();
            $table->boolean('reprendre_model_vente')->nullable();
            $table->enum('typeDocument',['vente', 'devi', 'livraison', 'command_vente','command_achat']);
            $table->enum('typeDesign',['bloc','compact','photo'])->default('bloc');
            $table->longText('content');
            $table->boolean('signatureExpediteurModel')->default(0);
            $table->string('mention_expediteur')->nullable();
            $table->string('image_expediteur')->nullable();
            $table->boolean('signatureDestinataireModel')->default(0);
            $table->string('mention_destinataire')->nullable();
            $table->boolean('autresImagesModel')->default(0);
            $table->string('image')->nullable();
            $table->boolean('conditionsPaiementModel')->default(0);
            $table->string('conditionPaiement')->nullable();
            $table->boolean('coordonneesBancairesModel')->default(0);
            $table->string('titulaireCompte')->nullable();
            $table->string('IBAN')->nullable();
            $table->string('BIC')->nullable();
            $table->boolean('notePiedPageModel')->default(0);
            $table->string('peidPage')->nullable();
            $table->longText('css')->nullable();
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
        Schema::dropIfExists('model_documents');
    }
};
