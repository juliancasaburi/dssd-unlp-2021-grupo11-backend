<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSociedadesAnonimasPaisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sociedades_anonimas_paises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sociedad_anonima_id');
            $table->unsignedBigInteger('pais_id');
            $table->timestamps();
            $table->foreign('sociedad_anonima_id')
                ->references('id')
                ->on('sociedades_anonimas');
            $table->foreign('pais_id')
                ->references('id')
                ->on('paises');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sociedades_anonimas_paises');
    }
}
