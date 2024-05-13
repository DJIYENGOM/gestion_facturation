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
            $table->string('nom_article');
            $table->text('description')->nullable();
            $table->decimal('prix_unitaire'); 
            $table->decimal('prix_promo')->nullable();
            $table->enum('type_article', ['produit', 'service']);
            $table->foreignId('promo_id')->nullable()->constrained('promos')->onDelete('set null')->nullable();
            $table->foreignId('sousUtilisateur_id')->constrained('sous__utilisateurs')->onDelete('cascade');
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
