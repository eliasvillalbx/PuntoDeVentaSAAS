<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'id_empresa', 'nombre', 'slug', 'descripcion', 'activa',
    ];

    /** Relación */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    /** Scope empresa */
    public function scopeDeEmpresa(Builder $q, int $empresaId): Builder
    {
        return $q->where('id_empresa', $empresaId);
    }

    /** Slug auto */
    protected static function booted(): void
    {
        static::saving(function (self $cat) {
            if (empty($cat->slug) && !empty($cat->nombre)) {
                $cat->slug = Str::slug($cat->nombre);
            }
        });
    }
}
