<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SuscripcionPagadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $provider,       // stripe|clip
        public int $empresaId,
        public string $empresaNombre,
        public string $planDb,          // 1_mes|6_meses|1_año|3_años
        public string $planLabel,       // Mensual|Semestral|Anual|3 Años
        public string $fechaInicio,     // YYYY-mm-dd HH:ii:ss
        public string $fechaVenc,       // YYYY-mm-dd HH:ii:ss
        public ?int $amountCents = null,
        public ?string $currency = null,
        public ?string $reference = null, // cs_test_... o me_reference_id
        public bool $renovada = false,
    ) {}

    public function via($notifiable): array
    {
        // Si no hay email, manda solo a sistema
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $montoTxt = '';
        if ($this->amountCents !== null && $this->currency) {
            $montoTxt = number_format($this->amountCents / 100, 2) . ' ' . strtoupper($this->currency);
        }

        return (new MailMessage)
            ->subject('Pago recibido - Suscripción ' . ($this->renovada ? 'renovada' : 'activada'))
            ->greeting('Hola ' . ($notifiable->name ?? ''))
            ->line('Se recibió un pago y la suscripción fue ' . ($this->renovada ? 'renovada' : 'activada') . '.')
            ->line('Empresa: ' . $this->empresaNombre . ' (ID: ' . $this->empresaId . ')')
            ->line('Plan: ' . $this->planLabel . ' (' . $this->planDb . ')')
            ->when($montoTxt !== '', fn($msg) => $msg->line('Monto: ' . $montoTxt))
            ->line('Vigencia: ' . $this->fechaInicio . ' → ' . $this->fechaVenc)
            ->when(!empty($this->reference), fn($msg) => $msg->line('Referencia: ' . $this->reference))
            ->line('Si no ves el acceso reflejado de inmediato, espera unos minutos y vuelve a entrar.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'          => 'suscripcion_pagada',
            'provider'      => $this->provider,
            'empresa_id'    => $this->empresaId,
            'empresa_nombre'=> $this->empresaNombre,
            'plan'          => $this->planDb,
            'plan_label'    => $this->planLabel,
            'fecha_inicio'  => $this->fechaInicio,
            'fecha_vencimiento' => $this->fechaVenc,
            'amount_cents'  => $this->amountCents,
            'currency'      => $this->currency,
            'reference'     => $this->reference,
            'renovada'      => $this->renovada,
        ];
    }
}
