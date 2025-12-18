<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Muestra la vista para restablecer la contraseña.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Maneja la solicitud de cambio de contraseña.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Validaciones en español
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo no es válido.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'token.required' => 'El token de seguridad falta o es inválido.',
        ]);

        // 2. Intentamos restablecer la contraseña
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // 3. Respuesta de Éxito
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', '¡Tu contraseña ha sido restablecida correctamente! Ya puedes iniciar sesión.');
        }

        // 4. Respuesta de Error (Traducida manualmente)
        $errorMessage = match ($status) {
            Password::INVALID_USER => 'No encontramos un usuario registrado con ese correo electrónico.',
            Password::INVALID_TOKEN => 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.',
            default => 'Ocurrió un error inesperado al restablecer la contraseña.',
        };

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $errorMessage]);
    }
}