<?php
// database/migrations/2025_10_27_000000_create_suscripciones_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();
            $table->enum('plan', ['1_mes','6_meses','1_año','3_años']);

            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_vencimiento');

            $table->enum('estado', ['activa','vencida'])->default('activa')->index();
            $table->boolean('renovado')->default(false);

            $table->timestamps();

            $table->index(['empresa_id','estado']);
            $table->index(['fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
