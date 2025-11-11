<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_compra')->index();
            $table->unsignedBigInteger('id_producto')->index();
            $table->decimal('cantidad', 10, 2);
            $table->decimal('costo_unitario', 10, 2);
            $table->decimal('descuento', 10, 2)->nullable();
            $table->decimal('total_linea', 10, 2);
            $table->timestamps();

            $table->foreign('id_compra')->references('id')->on('compras')->cascadeOnDelete();
            $table->foreign('id_producto')->references('id')->on('productos')->restrictOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('detalle_compras');
    }
};
