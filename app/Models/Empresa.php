<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        // Identidad legal / fiscal
        'razon_social',
        'nombre_comercial',
        'rfc',
        'tipo_persona',

        // Régimen
        'regimen_fiscal_code',

        // Contacto
        'email',
        'telefono',
        'sitio_web',

        // Domicilio
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'ciudad',
        'estado',
        'pais',
        'codigo_postal',

        // Operación
        'activa',
        'timezone',
        'logo_path',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    # Relaciones
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_empresa');
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'empresa_id');
    }

    # Helpers
    public function getDisplayNameAttribute(): string
    {
        return $this->nombre_comercial ?: $this->razon_social;
    }

    # Scopes útiles
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
