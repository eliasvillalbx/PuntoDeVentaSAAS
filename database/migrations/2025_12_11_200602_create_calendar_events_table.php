<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            // Empresa a la que pertenece el evento
            $table->unsignedBigInteger('empresa_id');

            // Usuario al que va dirigido el evento (responsable)
            $table->unsignedBigInteger('user_id')->nullable();

            // Quien creó el evento
            $table->unsignedBigInteger('created_by');

            $table->string('title');
            $table->text('description')->nullable();

            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->boolean('all_day')->default(false);

            // Opcional: color en el calendario
            $table->string('color', 20)->nullable();

            $table->timestamps();

            // Relaciones
            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index(['empresa_id', 'start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
