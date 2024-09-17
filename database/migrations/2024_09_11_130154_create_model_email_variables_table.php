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
        Schema::create('model_email_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_modele_id')->constrained('email_modeles')->onDelete('cascade');
            $table->foreignId('variable_email_id')->constrained('variable_emails')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_email_variables');
    }
};
