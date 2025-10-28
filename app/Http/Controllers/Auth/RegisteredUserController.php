<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        // Vista Breeze por defecto: resources/views/auth/register.blade.php
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'            => ['required','string','max:100'],
            'apellido_paterno'  => ['required','string','max:100'],
            'apellido_materno'  => ['nullable','string','max:100'],
            'telefono'          => ['nullable','string','max:20'],
            'email'             => ['required','string','lowercase','email','max:255', Rule::unique(User::class)],
            'password'          => ['required','confirmed','min:8'],
            'id_empresa'        => ['nullable','integer','exists:empresas,id'],
        ]);

        try {
            $user = User::create([
                'nombre'            => $data['nombre'],
                'apellido_paterno'  => $data['apellido_paterno'],
                'apellido_materno'  => $data['apellido_materno'] ?? null,
                'telefono'          => $data['telefono'] ?? null,
                'email'             => $data['email'],
                'password'          => $data['password'], // se hashea por cast 'hashed'
                'id_empresa'        => $data['id_empresa'] ?? null,
            ]);

            // (Opcional) rol por defecto:
            // $user->assignRole('administrador');

            event(new Registered($user));
            Auth::login($user);

            return redirect()->intended(route('dashboard', absolute: false))
                ->with('success','¡Registro completado!');
        } catch (\Throwable $e) {
            Log::error('Error al registrar usuario', ['e' => $e]);
            return back()->withInput()->withErrors('No se pudo completar el registro.');
        }
    }
}
