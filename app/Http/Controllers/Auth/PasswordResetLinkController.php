<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Muestra la vista para solicitar el enlace de recuperación.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Maneja la solicitud de envío del enlace de recuperación.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validación con mensajes directos en español
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El campo de correo electrónico es obligatorio.',
            'email.email'    => 'Por favor, introduce una dirección de correo válida.',
        ]);

        // 2. Intentamos enviar el enlace
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // 3. Verificamos el estado y respondemos en español manualmente
        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', '¡Te hemos enviado el enlace de recuperación a tu correo!');
        }

        // Si falló (generalmente porque el usuario no existe o hay que esperar)
        // Determinamos el mensaje de error:
        $errorMessage = match ($status) {
            Password::INVALID_USER => 'No encontramos ningún usuario registrado con ese correo electrónico.',
            Password::THROTTLED => 'Por favor, espera unos momentos antes de intentar de nuevo.',
            default => 'Ocurrió un error al intentar enviar el enlace.',
        };

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $errorMessage]);
    }
}