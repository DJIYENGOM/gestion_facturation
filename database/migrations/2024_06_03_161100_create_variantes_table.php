<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantesTable extends Migration
{
    public function up()
    {
        Schema::create('variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->string('nomVariante')->nullable();
            $table->integer('quantiteVariante')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('variantes');
    }
}
