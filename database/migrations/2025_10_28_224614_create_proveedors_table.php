<?php
// database/migrations/2025_10_28_000020_create_proveedores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->index();

            $table->string('nombre', 180);
            $table->string('rfc', 13)->nullable()->index();
            $table->string('email', 180)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('contacto', 120)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['id_empresa','nombre']);

            $table->foreign('id_empresa')
                ->references('id')->on('empresas')
                ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('proveedores');
    }
};
