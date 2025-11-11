<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'subtotal'    => 'decimal:2',
        'iva'         => 'decimal:2',
        'total'       => 'decimal:2',
    ];

    /** Relación con detalle_ventas (el controlador usa `detalle`) */
    public function detalle(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    /** Empresa */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /** Vendedor/usuario responsable */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Cliente */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
