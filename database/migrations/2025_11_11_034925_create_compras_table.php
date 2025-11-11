<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_empresa')->index();
            $table->unsignedBigInteger('id_proveedor')->index();
            $table->unsignedBigInteger('id_usuario')->index(); // quien registra
            $table->date('fecha_compra')->index();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('estatus', ['borrador','orden_compra','recibida','cancelada'])->default('borrador')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Ajusta nombres de tablas/PKs si usas otros
            $table->foreign('id_proveedor')->references('id')->on('proveedores')->restrictOnDelete();
            $table->foreign('id_usuario')->references('id')->on('users')->restrictOnDelete();
            // id_empresa: si tienes tabla empresas/empresas.id, puedes agregar FK:
            // $table->foreign('id_empresa')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('compras');
    }
};
