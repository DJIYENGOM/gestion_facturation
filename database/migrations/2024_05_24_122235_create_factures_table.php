<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('num_facture')->nullable();
            $table->date('date_creation');
            $table->string('type_facture')->nullable()->default('simple');
            $table->decimal('reduction_facture')->nullable();
            $table->decimal('prix_HT', 10, 2)->nullable()->default(0);
            $table->decimal('prix_TTC', 10, 2)->nullable()->default(0);
            $table->text('note_fact')->nullable();
            $table->date('date_paiement')->nullable();
            $table->enum('active_Stock', ['non', 'oui'])->default('non');
            $table->enum('statut_paiement', ['payer','en_attente','brouillon'])->default('brouillon');
            $table->enum('archiver', ['oui', 'non'])->default('non');
            $table->enum('type_paiement', ['immediat', 'echeance', 'facture_Accompt'])->default('facture_Accompt');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('cascade');
            $table->foreignId('id_comptable')->nullable()->constrained('compte_comptables')->onDelete('set null');
            $table->foreignId('id_paiement')->nullable()->constrained('payements')->onDelete('set null');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('devi_id')->nullable()->constrained('devis')->onDelete('set null');
            $table->foreignId('bonCommande_id')->nullable()->constrained('bon_commandes')->onDelete('set null');
            $table->foreignId('id_recurrent')->nullable()->constrained('facture_recurrentes')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
