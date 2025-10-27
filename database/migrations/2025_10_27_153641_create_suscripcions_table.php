<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Corregido aquí
        Schema::create('suscripcions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->enum('plan', ['1_mes', '6_mes', '1_year', '3_year']);
            $table->date('fecha_inicio'); 
            $table->date('fecha_vencimiento'); 
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->boolean('renovado')->default(false);
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
        // Y corregido aquí también
        Schema::dropIfExists('suscripcions');
    }
};