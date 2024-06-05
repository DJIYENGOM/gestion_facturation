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
            $table->string('num_article')->unique();
            $table->string('nom_article');
            $table->text('description')->nullable();
            $table->decimal('prix_unitaire'); 
            $table->integer('quantite')->nullable();
            $table->decimal('benefice')->nullable();
            $table->decimal('prix_achat')->nullable();
            $table->decimal('prix_promo')->nullable();
            $table->decimal('prix_tva')->nullable();
            $table->string('doc_externe')->nullable();
            $table->decimal('tva', 5, 2)->nullable();
            $table->decimal('benefice_promo')->nullable();
            $table->integer('quantite_alert')->nullable();
            $table->enum('type_article', ['produit', 'service']);
            $table->enum('unité', ['unite', 'kg', 'g', 'tonne', 'cm', 'l', 'm', 'm2','m3','h','jour','semaine','mois'])->nullable();
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
