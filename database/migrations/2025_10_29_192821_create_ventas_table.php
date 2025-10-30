<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete(); // opcional
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete(); // vendedor/gerente responsable
            $table->date('fecha_venta')->index();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('estatus', ['borrador', 'prefactura', 'facturada', 'cancelada'])->default('borrador')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'usuario_id', 'estatus']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
