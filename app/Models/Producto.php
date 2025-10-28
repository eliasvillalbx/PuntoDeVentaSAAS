<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'id_empresa','categoria_id','nombre','slug','sku','precio','costo_referencial',
        'moneda_venta','stock','descripcion','imagen_path','activo',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedor', 'producto_id', 'proveedor_id')
            ->withPivot(['sku_proveedor','costo','moneda','lead_time_dias','moq','preferido','activo'])
            ->withTimestamps();
    }

    /** Scope empresa */
    public function scopeDeEmpresa(Builder $q, int $empresaId): Builder
    {
        return $q->where('id_empresa', $empresaId);
    }

    /** Slug auto */
    protected static function booted(): void
    {
        static::saving(function (self $p) {
            if (empty($p->slug) && !empty($p->nombre)) {
                $p->slug = Str::slug($p->nombre);
            }
        });
    }
}
