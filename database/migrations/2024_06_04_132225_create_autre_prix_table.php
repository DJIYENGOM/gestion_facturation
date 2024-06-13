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
    {  Schema::create('autre_prix', function (Blueprint $table) {
        $table->id();
        $table->foreignId('article_id')->constrained('articles')->onDelete('cascade')->nullable();
        $table->string('titrePrix')->nullable();
        $table->decimal('montant', 10, 2)->nullable();
        $table->decimal('tva', 5, 2)->nullable();
        $table->decimal('montantTva', 10, 2)->nullable();
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('autre_prix');
}
};
