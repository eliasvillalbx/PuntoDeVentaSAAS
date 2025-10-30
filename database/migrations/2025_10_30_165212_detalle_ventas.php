<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento', 10, 2)->nullable();
            $table->decimal('total_linea', 10, 2);
            $table->timestamps();

            $table->index(['venta_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
