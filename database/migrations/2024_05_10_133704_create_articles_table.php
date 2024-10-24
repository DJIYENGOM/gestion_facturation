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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('num_article')->nullable();
            $table->string('nom_article');
            $table->boolean('active_article')->default(1);
            $table->text('description')->nullable();
            $table->decimal('prix_unitaire', 10, 2); 
            $table->integer('quantite')->nullable();
            $table->decimal('benefice')->nullable();
            $table->decimal('prix_promo',10, 2)->nullable();
            $table->decimal('prix_tva', 10, 2)->nullable();
            $table->string('doc_externe')->nullable();
            $table->decimal('tva', 5, 2)->nullable();
            $table->decimal('benefice_promo', 10, 2)->nullable();
            $table->integer('quantite_alert')->nullable();
            $table->enum('active_Stock', ['non', 'oui'])->default('non');
            $table->integer('quantite_disponible')->nullable();
            $table->enum('type_article', ['produit', 'service']);
            $table->string('code_barre')->nullable();
            $table->enum('unité', ['unite', 'kg', 'g', 'tonne', 'cm', 'l', 'm', 'm2','m3','h','jour','semaine','mois'])->nullable();
            $table->decimal('tva_achat', 5, 2)->nullable();
            $table->decimal('prix_ht_achat', 10, 2)->nullable();
            $table->decimal('prix_ttc_achat', 10, 2)->nullable();
            $table->foreignId('promo_id')->nullable()->constrained('promos')->onDelete('set null')->nullable();
            $table->foreignId('id_comptable')->nullable()->constrained('compte_comptables')->onDelete('set null');
            $table->foreignId('sousUtilisateur_id')->nullable()->constrained('sous__utilisateurs')->onDelete('set null');        //de lui affecter la valeu NULL si l'utilisateur est supprimer  
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('id_categorie_article')->nullable()->constrained('categorie_articles')->onDelete('set null');   //garder sa valeur meme si la categorie article est supprimer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
