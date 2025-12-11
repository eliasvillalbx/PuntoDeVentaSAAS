<?php

namespace App\Notifications;

use App\Models\CalendarEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;

class CalendarEventAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    protected CalendarEvent $event;
    protected string $action; // created | updated | deleted

    public function __construct(CalendarEvent $event, string $action = 'created')
    {
        $this->event  = $event;
        $this->action = $action;
    }

    /**
     * Canales: base de datos + correo
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Payload para la BD (tabla notifications->data).
     */
    public function toDatabase($notifiable): array
    {
        $creator = $this->event->creator;
        $empresa = $this->event->empresa;

        return [
            'event_id'         => $this->event->id,
            'title'            => $this->event->title,
            'start'            => optional($this->event->start)?->toIso8601String(),
            'end'              => optional($this->event->end)?->toIso8601String(),
            'action'           => $this->action, // created / updated / deleted

            'empresa_id'       => $this->event->empresa_id,
            'empresa_nombre'   => $empresa?->display_name,

            'created_by_id'    => $creator?->id,
            'created_by_name'  => $creator
                ? trim(($creator->nombre ?? '') . ' ' . ($creator->apellido_paterno ?? ''))
                : null,

            'all_day'          => $this->event->all_day,
            'color'            => $this->event->color,
        ];
    }

    /**
     * Payload para correo.
     */
    public function toMail($notifiable): MailMessage
    {
        $empresa = $this->event->empresa;
        $empresaNombre = $empresa?->display_name ?: 'la empresa';

        $url = route('calendar.index', [
            'empresa_id' => $this->event->empresa_id,
        ]);

        $subjectBase = "Evento de calendario";

        if ($this->action === 'created') {
            $subjectBase = 'Nuevo evento asignado';
        } elseif ($this->action === 'updated') {
            $subjectBase = 'Evento actualizado';
        } elseif ($this->action === 'deleted') {
            $subjectBase = 'Evento eliminado';
        }

        $mail = (new MailMessage)
            ->subject($subjectBase . " - {$empresaNombre}");

        if ($notifiable->name ?? null) {
            $mail->greeting('Hola ' . $notifiable->name . ',');
        }

        if ($this->action === 'created') {
            $mail->line('Se te ha asignado un nuevo evento en el calendario.');
        } elseif ($this->action === 'updated') {
            $mail->line('Se ha actualizado un evento que tienes asignado.');
        } elseif ($this->action === 'deleted') {
            $mail->line('Se ha eliminado un evento que tenías asignado.');
        }

        $mail->line('Título: ' . $this->event->title);
        $mail->line('Empresa: ' . $empresaNombre);

        if ($this->event->start) {
            $mail->line('Inicio: ' . $this->event->start->format('d/m/Y H:i'));
        }
        if ($this->event->end) {
            $mail->line('Fin: ' . $this->event->end->format('d/m/Y H:i'));
        }

        if ($this->action !== 'deleted') {
            $mail->action('Ver calendario', $url);
        }

        return $mail;
    }
}
