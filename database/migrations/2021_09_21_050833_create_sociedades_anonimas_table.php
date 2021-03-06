<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSociedadesAnonimasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sociedades_anonimas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->dateTime('fecha_creacion');
            $table->string('domicilio_legal');
            $table->string('domicilio_real');
            $table->string('email_apoderado');
            $table->string('numero_expediente')->nullable();
            $table->string('numero_hash')->nullable();
            $table->string('url_codigo_QR')->nullable();
            $table->string('estado_evaluacion');
            $table->string('bonita_case_id');
            $table->integer('cantidad_rechazos_mesa_entradas')->default(0);
            $table->integer('cantidad_rechazos_area_legales')->default(0);
            $table->unsignedBigInteger('apoderado_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // User stamps
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('apoderado_id')
                ->references('id')
                ->on('socios')
                ->nullOnDelete();
        });

        Schema::table('socios', function (Blueprint $table) {
            $table->foreign('sociedad_anonima_id')
                ->references('id')
                ->on('sociedades_anonimas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sociedades_anonimas');
    }
}
