<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();

            // Empresa dueña de la conversación (nivel empresarial)
            $table->unsignedBigInteger('empresa_id');

            // direct = 1 a 1, group = grupo
            $table->enum('type', ['direct', 'group'])->default('group');

            // Nombre del grupo (nullable si es direct)
            $table->string('name')->nullable();

            // Usuario que creó la conversación
            $table->unsignedBigInteger('created_by');

            // Para activar/desactivar conversación
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id')->on('empresas')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('cascade');

            // Para listar rápido conversaciones de una empresa
            $table->index(['empresa_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
