<?php
// app/Models/Suscripcion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Suscripcion extends Model
{
    protected $table = 'suscripciones';

    protected $fillable = [
        'empresa_id',
        'plan',
        'fecha_inicio',
        'fecha_vencimiento',
        'estado',
        'renovado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_vencimiento' => 'datetime',
        'renovado' => 'boolean',
    ];

    public const PLANES = ['1_mes','6_meses','1_año','3_años'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /** Devuelve la fecha de vencimiento calculada a partir del inicio y el plan */
    public static function calcularVencimiento(Carbon $inicio, string $plan): Carbon
    {
        return match ($plan) {
            '1_mes'   => $inicio->copy()->addMonthNoOverflow(),
            '6_meses' => $inicio->copy()->addMonthsNoOverflow(6),
            '1_año'   => $inicio->copy()->addYearNoOverflow(),
            '3_años'  => $inicio->copy()->addYearsNoOverflow(3),
            default   => throw new \InvalidArgumentException('Plan inválido'),
        };
    }

    /** ¿Sigue vigente (activa y sin vencer)? */
    public function getVigenteAttribute(): bool
    {
        return $this->estado === 'activa' && now()->lessThanOrEqualTo($this->fecha_vencimiento);
    }

    /* Scopes útiles */
    public function scopeActiva($q) { return $q->where('estado', 'activa'); }
    public function scopeDeEmpresa($q, int $empresaId) { return $q->where('empresa_id', $empresaId); }
}
