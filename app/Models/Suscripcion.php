<?php
// app/Models/Suscripcion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
        'fecha_inicio'      => 'date',
        'fecha_vencimiento' => 'date',
        'renovado'          => 'boolean',
    ];

    /* =================== Relaciones =================== */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    /* =================== Scopes útiles =================== */
    public function scopeDeEmpresa(Builder $q, int $empresaId): Builder
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopeActiva(Builder $q): Builder
    {
        return $q->where('estado', 'activa')
                 ->whereDate('fecha_vencimiento', '>=', now()->toDateString());
    }

    public function scopeVencida(Builder $q): Builder
    {
        return $q->where(function ($w) {
            $w->where('estado', 'vencida')
              ->orWhereDate('fecha_vencimiento', '<', now()->toDateString());
        });
    }

    /* =================== Helpers =================== */
    public static function calcularVencimiento(Carbon $inicio, string $plan): Carbon
    {
        $plan = strtolower($plan);
        $months = match ($plan) {
            'mensual'     => 1,
            'trimestral'  => 3,
            'anual'       => 12,
            default       => 1,
        };

        // Evita desbordes (28->29/30/31)
        return (clone $inicio)->addMonthsNoOverflow($months);
    }

    public function getEstaVigenteAttribute(): bool
    {
        return $this->estado === 'activa'
            && $this->fecha_vencimiento?->isFuture();
    }

    protected static function booted(): void
    {
        // Cada vez que guardemos, si ya venció, marcamos estado "vencida" automáticamente
        static::saving(function (Suscripcion $s) {
            if ($s->fecha_vencimiento && $s->fecha_vencimiento->isPast() && $s->estado === 'activa') {
                $s->estado = 'vencida';
            }
        });
    }
}
