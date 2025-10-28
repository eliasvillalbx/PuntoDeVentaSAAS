<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('rfc', 13)->unique();
            $table->enum('tipo_persona', ['moral','fisica'])->default('moral');

            $table->string('regimen_fiscal_code', 10)->nullable()->index();

            $table->string('email', 160)->nullable()->unique();
            $table->string('telefono', 20)->nullable()->index();
            $table->string('sitio_web', 200)->nullable();

            $table->string('calle', 120)->nullable();
            $table->string('numero_exterior', 20)->nullable();
            $table->string('numero_interior', 20)->nullable();
            $table->string('colonia', 120)->nullable();
            $table->string('municipio', 120)->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('estado', 120)->nullable();
            $table->string('pais', 80)->default('México');
            $table->string('codigo_postal', 10)->nullable()->index();

            // Operación
            $table->boolean('activa')->default(true)->index();
            $table->string('timezone', 64)->default('America/Mexico_City');
            $table->string('logo_path', 255)->nullable();

            $table->timestamps();

            $table->index(['razon_social']);
            $table->index(['nombre_comercial']);


        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('id_empresa')
                ->nullable()
                ->after('password')
                ->constrained('empresas')
                ->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
