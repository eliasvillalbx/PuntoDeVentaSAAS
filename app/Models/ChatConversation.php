<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    use HasFactory;

    protected $table = 'chat_conversations';

    protected $fillable = [
        'empresa_id',
        'type',
        'name',
        'created_by',
        'is_active',
    ];

    public function empresa()
    {
        // Ajusta el modelo si se llama diferente
        return $this->belongsTo(\App\Models\Empresas::class, 'empresa_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * OJO: aquí va 'conversation_id' (como en tu migración),
     * NO 'chat_conversation_id'
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_conversation_user', 'conversation_id', 'user_id')
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}
