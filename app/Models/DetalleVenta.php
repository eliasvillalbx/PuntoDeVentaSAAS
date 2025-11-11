<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas'; // ajusta si tu tabla tiene otro nombre

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'total_linea',
    ];

    protected $casts = [
        'cantidad'        => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'descuento'       => 'decimal:2',
        'total_linea'     => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
