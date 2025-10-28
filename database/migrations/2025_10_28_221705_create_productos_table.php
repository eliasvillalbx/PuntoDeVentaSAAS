<?php
// database/migrations/2025_10_28_000010_create_productos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->index();
            $table->unsignedBigInteger('categoria_id')->nullable()->index();

            $table->string('nombre', 180);
            $table->string('slug', 200)->index();
            $table->string('sku', 100)->nullable()->index();

            $table->decimal('precio', 12, 2)->default(0);
            $table->decimal('costo_referencial', 12, 2)->nullable();
            $table->string('moneda_venta', 3)->default('MXN');
            $table->integer('stock')->default(0);

            $table->text('descripcion')->nullable();
            $table->string('imagen_path')->nullable();
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['id_empresa','nombre']);
            $table->unique(['id_empresa','slug']);
            $table->unique(['id_empresa','sku']);

            $table->foreign('id_empresa')
                ->references('id')->on('empresas')
                ->onDelete('cascade');

            $table->foreign('categoria_id')
                ->references('id')->on('categorias')
                ->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('productos');
    }
};
