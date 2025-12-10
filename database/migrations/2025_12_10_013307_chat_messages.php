<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id'); // quién envía

            $table->text('message')->nullable();

            // Por si luego quieres archivos/imágenes
            $table->enum('type', ['text', 'image', 'file', 'system'])->default('text');
            $table->string('file_path')->nullable();

            $table->timestamps();

            $table->foreign('conversation_id')
                ->references('id')->on('chat_conversations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
