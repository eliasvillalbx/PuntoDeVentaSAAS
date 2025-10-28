<?php
// database/migrations/2025_10_28_000000_create_categorias_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->index();
            $table->string('nombre', 120);
            $table->string('slug', 150)->index();
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['id_empresa', 'nombre']);
            $table->unique(['id_empresa', 'slug']);

            $table->foreign('id_empresa')
                ->references('id')->on('empresas')
                ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('categorias');
    }
};
