<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'email',
        'password',
        'id_empresa',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // Laravel 12: encripta automáticamente cuando se asigna
        'password'          => 'hashed',
    ];

    # Relaciones
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    # Accesores/Helpers
    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    # Scopes útiles
    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('id_empresa', $empresaId);
    }

 public function chatConversations()
{
    // Igual: conversation_id, no chat_conversation_id
    return $this->belongsToMany(\App\Models\ChatConversation::class, 'chat_conversation_user', 'user_id', 'conversation_id')
        ->withPivot(['role', 'joined_at', 'left_at'])
        ->withTimestamps();
}

public function chatMessages()
{
    return $this->hasMany(\App\Models\ChatMessage::class, 'user_id');
}


}
