<?php
// database/migrations/2025_10_28_000030_create_producto_proveedor_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('producto_proveedor', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('producto_id')->index();
            $table->unsignedBigInteger('proveedor_id')->index();

            $table->string('sku_proveedor', 120)->nullable();
            $table->decimal('costo', 12, 2);
            $table->string('moneda', 3)->default('MXN');
            $table->unsignedInteger('lead_time_dias')->default(0);
            $table->unsignedInteger('moq')->default(1);
            $table->boolean('preferido')->default(false);
            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['producto_id','proveedor_id']);

            $table->foreign('producto_id')
                ->references('id')->on('productos')->onDelete('cascade');

            $table->foreign('proveedor_id')
                ->references('id')->on('proveedores')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('producto_proveedor');
    }
};
