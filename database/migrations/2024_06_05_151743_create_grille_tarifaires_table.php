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
        Schema::create('grille_tarifaires', function (Blueprint $table) {
            $table->id();
            $table->decimal('montantTarif', 10, 2)->default(0);
            $table->decimal('tva', 5, 2)->nullable();
            $table->decimal('montantTva', 10, 2)->nullable()->default(0);
            $table->foreignId('idArticle')->constrained('articles')->onDelete('cascade');
            $table->foreignId('idClient')->constrained('clients')->onDelete('cascade');
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
        Schema::dropIfExists('grille_tarifaires');
    }
};
