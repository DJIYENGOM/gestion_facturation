<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntrepotArticlesTable extends Migration
{
    public function up()
    {
        Schema::create('entrepot_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->nullable()->constrained('articles')->onDelete('cascade');
            $table->foreignId('entrepot_id')->nullable()->constrained('entrepots')->onDelete('cascade');
            $table->integer('quantiteArt_entrepot')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('entrepot_articles');
    }
}

