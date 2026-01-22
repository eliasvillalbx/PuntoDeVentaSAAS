<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Personaliza el correo de restablecimiento de contraseña
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            try {
                $url = url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false));

                $expire = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

                return (new MailMessage)
                    ->subject('Restablecer contraseña — POS Empresarial')
                    ->greeting('Hola 👋')
                    ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                    ->action('Restablecer contraseña', $url)
                    ->line("Este enlace expira en {$expire} minutos.")
                    ->line('Si tú no solicitaste el restablecimiento, no necesitas hacer nada.')
                    ->salutation('Saludos, POS Empresarial');

            } catch (\Throwable $e) {
                report($e);

                // Fallback seguro: si algo falla, manda un mensaje mínimo
                $url = url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false));

                return (new MailMessage)
                    ->subject('Restablecer contraseña — POS Empresarial')
                    ->greeting('Hola 👋')
                    ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                    ->action('Restablecer contraseña', $url)
                    ->salutation('Saludos, POS Empresarial');
            }
        });
    }
}
