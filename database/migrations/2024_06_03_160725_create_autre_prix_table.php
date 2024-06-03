<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutrePrixTable extends Migration
{
    public function up()
    {
        Schema::create('autre_prix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->string('titrePrix');
            $table->decimal('montant', 10, 2);
            $table->decimal('tva', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('autre_prix');
    }
}

