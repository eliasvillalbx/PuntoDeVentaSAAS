<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuscripcionPagadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $empresaId,
        public string $empresaNombre,
        public int $suscripcionId,
        public string $plan,
        public string $stripeSessionId,
        public string $tipo,                 // creada | renovada
        public ?float $monto = null,         // ✅ opcional
        public string $moneda = 'MXN',        // ✅ opcional
        public ?string $periodoHumano = null  // ✅ opcional (ej: "1 mes", "6 meses", "1 año")
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function planHumano(): string
    {
        // Ajusta si quieres otros textos
        return match ($this->plan) {
            '1_mes'   => 'Plan Mensual',
            '6_meses' => 'Plan Semestral',
            '1_año'   => 'Plan Anual',
            '3_años'  => 'Plan 3 Años',
            default   => 'Plan',
        };
    }

    private function formatoMonto(): string
    {
        if ($this->monto === null) return '—';
        return '$' . number_format($this->monto, 2) . ' ' . strtoupper($this->moneda);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $titulo = $this->tipo === 'renovada'
            ? 'Pago procesado · Suscripción renovada'
            : 'Pago procesado · Suscripción activada';

        $subtitulo = "Empresa: {$this->empresaNombre}";

        return (new MailMessage)
            ->subject($titulo)
            ->greeting("Hola {$notifiable->nombre_completo} 👋")
            ->line("Se procesó correctamente un pago de suscripción.")
            ->line($subtitulo)
            ->line("**" . $this->planHumano() . "**" . ($this->periodoHumano ? " ({$this->periodoHumano})" : ""))
            ->line("Monto: **" . $this->formatoMonto() . "**")
            ->line("Ya puedes seguir usando el sistema con normalidad.")
            ->salutation('ATIENDEMAS');
    }

    public function toArray(object $notifiable): array
    {
        $accion = $this->tipo === 'renovada' ? 'renovada' : 'activada';

        return [
            'type' => 'suscripcion_pagada',
            'title' => "Pago procesado",
            'message' => "Suscripción {$accion} para {$this->empresaNombre}.",
            'empresa_id' => $this->empresaId,
            'suscripcion_id' => $this->suscripcionId,
            'plan' => $this->plan,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'periodo' => $this->periodoHumano,
        ];
    }
}
