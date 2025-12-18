<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Cliente extends Model
{
    use SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id',
        'tipo_persona',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'razon_social',
        'rfc',
        'email',
        'telefono',
        'calle',
        'numero_ext',
        'numero_int',
        'colonia',
        'municipio',
        'estado',
        'cp',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Relaciones
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    // Accesor de nombre mostrado
    public function getNombreMostrarAttribute(): string
    {
        if ($this->tipo_persona === 'moral') {
            return (string) $this->razon_social ?: '—';
        }
        return trim(sprintf(
            '%s %s %s',
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno
        )) ?: '—';
    }

    /// Scope por empresa
    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }
}
