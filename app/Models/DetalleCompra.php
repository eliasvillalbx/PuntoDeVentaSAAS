<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compras';

    protected $fillable = [
        'id_compra',
        'id_producto',
        'cantidad',
        'costo_unitario',
        'descuento',
        'total_linea',
    ];

    protected $casts = [
        'cantidad'       => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'descuento'      => 'decimal:2',
        'total_linea'    => 'decimal:2',
    ];

    /** Relaciones */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'id_compra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}
