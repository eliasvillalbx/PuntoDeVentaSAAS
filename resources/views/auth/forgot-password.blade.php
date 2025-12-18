<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar Contraseña — POS Empresarial</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind + App --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <script defer src="https://unpkg.com/alpinejs"></script>

  <style>
    .card { background: #ffffff; }
    .divider { height:1px; background:linear-gradient(90deg,rgba(0,0,0,.06),rgba(0,0,0,.02),rgba(0,0,0,.06)); }
  </style>
</head>
<body class="h-full antialiased text-slate-800">
  <main class="min-h-full">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col md:flex-row">
      
      {{-- ================= Columna Izquierda (Branding / Ayuda) ================= --}}
      <section class="hidden md:flex md:w-1/2 items-center justify-center border-r border-slate-200/80 px-10">
        <div class="w-full max-w-md">
          <div class="mb-8">
            {{-- Logo --}}
            <div class="flex items-center gap-3 select-none">
                {{-- Texto del Logo ajustado como pediste --}}
                <div class="inline-flex flex-col items-start gap-0">
                    <span class="text-2xl font-black text-slate-900 leading-none tracking-tighter">
                        POS
                    </span>
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.25em] ml-0.5">
                        Empresarial
                    </span>
                </div>
            </div>
          </div>

          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
            ¿Problemas para acceder?
          </h1>
          <p class="mt-3 text-slate-600">
            No te preocupes, ocurre a menudo. Te ayudaremos a restablecer tu seguridad para que vuelvas a gestionar tu negocio cuanto antes.
          </p>

          {{-- Caja informativa --}}
          <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-600 mt-0.5">info</span>
                <div>
                    <h3 class="text-sm font-medium text-slate-900">¿Cómo funciona?</h3>
                    <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                        Te enviaremos un enlace seguro a tu correo electrónico. Al hacer clic en él, podrás definir una nueva contraseña inmediatamente.
                    </p>
                </div>
            </div>
          </div>
        </div>
      </section>

      {{-- ================= Columna Derecha (Formulario) ================= --}}
      <section class="flex w-full md:w-1/2 items-center justify-center px-6 sm:px-8">
        <div class="w-full max-w-md">
          
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-900">Recuperar contraseña</h2>
            <p class="mt-1 text-sm text-slate-600">
                Ingresa tu correo electrónico y te enviaremos las instrucciones.
            </p>
          </div>

          {{-- Estado de sesión (Éxito al enviar correo) --}}
          @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-start gap-2">
                <span class="material-symbols-outlined text-lg">check_circle</span>
                <span>{{ session('status') }}</span>
            </div>
          @endif

          {{-- Errores de validación --}}
          @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              <div class="font-medium flex items-center gap-2">
                  <span class="material-symbols-outlined text-lg">error</span>
                  Ups, algo salió mal:
              </div>
              <ul class="mt-1 list-disc pl-8">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="card rounded-xl border border-slate-200 p-6 shadow-sm">
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
              @csrf

              {{-- EMAIL --}}
              <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  inputmode="email"
                  required
                  autofocus
                  value="{{ old('email') }}"
                  placeholder="tucorreo@empresa.com"
                  class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-blue-500"
                >
                @error('email')
                  <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- SUBMIT --}}
              <div class="pt-1">
                <button type="submit"
                        class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-medium text-white shadow hover:bg-black focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                  Enviar enlace de recuperación
                </button>
              </div>
            </form>
          </div>

          {{-- Volver al Login --}}
          <div class="mt-6 text-center">
             <p class="text-sm text-slate-600">
                 ¿Ya recordaste tu contraseña?
             </p>
             <a href="{{ route('login') }}" class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-slate-900 hover:text-blue-600 transition-colors">
                 <span class="material-symbols-outlined text-lg">arrow_back</span>
                 Volver al inicio de sesión
             </a>
          </div>

        </div>
      </section>
    </div>
  </main>
</body>
</html>