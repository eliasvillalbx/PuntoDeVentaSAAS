<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'id_empresa',
        'id_proveedor',
        'id_usuario',
        'fecha_compra',
        'subtotal',
        'iva',
        'total',
        'estatus',
        'observaciones',
    ];

    protected $casts = [
        'fecha_compra' => 'date:Y-m-d',
        'subtotal'     => 'decimal:2',
        'iva'          => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /** Relaciones */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    // Si tu modelo de empresa se llama 'Empresas', deja este; si es 'Empresa', cámbialo.
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresas::class, 'id_empresa');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'id_compra');
    }

    /** Scopes útiles */
    public function scopeEmpresa($query, int $empresaId)
    {
        return $query->where('id_empresa', $empresaId);
    }

    public function scopeEstatus($query, ?string $estatus)
    {
        return $estatus ? $query->where('estatus', $estatus) : $query;
    }

    public function scopeRangoFechas($query, ?string $inicio, ?string $fin)
    {
        if ($inicio) $query->whereDate('fecha_compra', '>=', $inicio);
        if ($fin)    $query->whereDate('fecha_compra', '<=', $fin);
        return $query;
    }
}
