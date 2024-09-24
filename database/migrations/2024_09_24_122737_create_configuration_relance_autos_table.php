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
        Schema::create('configuration_relance_autos', function (Blueprint $table) {
            $table->id();
            $table->boolean('envoyer_rappel_avant')->default(false);
            $table->integer('nombre_jour_avant')->default(5);
            $table->boolean('envoyer_rappel_apres')->default(false);
            $table->integer('nombre_jour_apres')->default(5);
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
        Schema::dropIfExists('configuration_relance_autos');
    }
};
