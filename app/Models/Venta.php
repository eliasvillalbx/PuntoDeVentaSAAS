<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'usuario_id',
        'fecha_venta',
        'subtotal',
        'iva',
        'total',
        'estatus',
        'observaciones',
    ];

    protected $casts = [
        'fecha_venta' => 'date',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function scopeDeEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function esPrefactura(): bool
    {
        return $this->estatus === 'prefactura';
    }

    public function esFacturada(): bool
    {
        return $this->estatus === 'facturada';
    }
}
