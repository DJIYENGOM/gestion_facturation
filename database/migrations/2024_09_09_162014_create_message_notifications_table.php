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
        Schema::create('message_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('message');
            $table->foreignId('article_id')->nullable()->constrained('articles')->onDelete('set null');
            $table->foreignId('facture_id')->nullable()->constrained('factures')->onDelete('set null');
            $table->foreignId('echeance_id')->nullable()->constrained('echeances')->onDelete('set null');
            $table->foreignId('devis_id')->nullable()->constrained('devis')->onDelete('set null');
            $table->foreignId('depense_id')->nullable()->constrained('depenses')->onDelete('set null');
            
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
        Schema::dropIfExists('message_notifications');
    }
};
