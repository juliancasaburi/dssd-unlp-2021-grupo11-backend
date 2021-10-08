<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSociedadesAnonimasEstadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sociedades_anonimas_estados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sociedad_anonima_id');
            $table->unsignedBigInteger('estado_id');
            $table->foreign('sociedad_anonima_id')
                ->references('id')
                ->on('sociedades_anonimas');
            $table->foreign('estado_id')
                ->references('id')
                ->on('estados');
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sociedades_anonimas_estados');
    }
}
