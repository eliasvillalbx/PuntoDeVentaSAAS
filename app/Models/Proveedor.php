<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'id_empresa','nombre','rfc','email','telefono','contacto','activo',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'producto_proveedor', 'proveedor_id', 'producto_id')
            ->withPivot(['sku_proveedor','costo','moneda','lead_time_dias','moq','preferido','activo'])
            ->withTimestamps();
    }

    /** Scope empresa */
    public function scopeDeEmpresa(Builder $q, int $empresaId): Builder
    {
        return $q->where('id_empresa', $empresaId);
    }

    public function compras()
    {
        return $this->hasMany(Compra::class, 'id_proveedor');
    }

}
