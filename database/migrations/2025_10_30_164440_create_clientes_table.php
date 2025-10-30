<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            // Relación con empresa
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            // Identidad del cliente (sin acceso al sistema)
            $table->enum('tipo_persona', ['fisica', 'moral'])->default('fisica');

            // Para persona física
            $table->string('nombre')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();

            // Para persona moral
            $table->string('razon_social')->nullable();

            // Datos comunes
            $table->string('rfc', 13)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();

            // Dirección (simple, puedes enlazar a tu tabla de direcciones si ya la usas)
            $table->string('calle')->nullable();
            $table->string('numero_ext', 20)->nullable();
            $table->string('numero_int', 20)->nullable();
            $table->string('colonia')->nullable();
            $table->string('municipio')->nullable();
            $table->string('estado')->nullable();
            $table->string('cp', 10)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Unicidad práctica por empresa (permitiendo NULLs)
            $table->unique(['empresa_id', 'email']);
            $table->unique(['empresa_id', 'rfc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
