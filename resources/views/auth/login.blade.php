<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión — POS Empresarial</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Tailwind + tu app (ajusta si no usas Vite) --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])

  {{-- Alpine para toggle de contraseña (si no lo cargas en app.js, deja este CDN) --}}
  <script defer src="https://unpkg.com/alpinejs"></script>

  <style>
    .card { background: #ffffff; }
    .divider { height:1px;background:linear-gradient(90deg,rgba(0,0,0,.06),rgba(0,0,0,.02),rgba(0,0,0,.06)); }
  </style>
</head>
<body class="h-full antialiased text-slate-800">
  <main class="min-h-full">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col md:flex-row">
      {{-- Columna izquierda: branding + mensaje de suscripción --}}
      <section class="hidden md:flex md:w-1/2 items-center justify-center border-r border-slate-200/80 px-10">
        <div class="w-full max-w-md">
          <div class="mb-8">
            {{-- Logo/Marca (placeholder) --}}
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-lg bg-slate-900 text-white grid place-items-center">
                <!-- ícono minimal POS -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M6 7v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7M8 11h8M8 15h5" />
                </svg>
              </div>
              <div class="text-lg font-semibold">POS Empresarial</div>
            </div>
          </div>

          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
            Acceso seguro a tu plataforma
          </h1>
          <p class="mt-3 text-slate-600">
            Inicia sesión para gestionar ventas, inventario, clientes y reportes.
          </p>

          {{-- Mensaje de suscripción (sin métricas ni trust bar) --}}
          <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-sm text-slate-700">
              ¿Eres nuevo? <span class="font-medium">Registra tu empresa</span> o
              <span class="font-medium">únete a una empresa existente</span>. Esto se manejará con códigos de invitación.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
              @if (Route::has('register.company'))
                <a href="{{ route('register.company') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">
                  Registrar empresa
                </a>
              @endif

              @if (Route::has('register.member'))
                <a href="{{ route('register.member') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">
                  Unirme a una empresa
                </a>
              @endif
            </div>
          </div>
        </div>
      </section>

      {{-- Columna derecha: formulario de login --}}
      <section class="flex w-full md:w-1/2 items-center justify-center px-6 sm:px-8">
        <div class="w-full max-w-md">
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-slate-900">Iniciar sesión</h2>
            <p class="mt-1 text-sm text-slate-600">Introduce tus credenciales para continuar.</p>
          </div>

          {{-- Estado de sesión (p. ej., enlace enviado) --}}
          @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
              {{ session('status') }}
            </div>
          @endif

          {{-- Resumen de errores --}}
          @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              <div class="font-medium">No pudimos iniciar sesión:</div>
              <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="card rounded-xl border border-slate-200 p-6 shadow-sm">
            <form method="POST" action="{{ route('login') }}" x-data="{ show: false }" novalidate class="space-y-5">
              @csrf

              {{-- EMAIL --}}
              <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Correo electrónico</label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  inputmode="email"
                  autocomplete="username"
                  required
                  value="{{ old('email') }}"
                  placeholder="tucorreo@empresa.com"
                  class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder-slate-400 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-blue-500"
                >
                @error('email')
                  <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- PASSWORD + toggle --}}
              <div>
                <div class="flex items-center justify-between">
                  <label for="password" class="block text-sm font-medium text-slate-700">Contraseña</label>
                  @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-sm text-slate-700 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                      ¿Olvidaste tu contraseña?
                    </a>
                  @endif
                </div>

                <div class="relative mt-1">
                  <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    placeholder="••••••••"
                    x-bind:type="show ? 'text' : 'password'"
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-slate-900 placeholder-slate-400 shadow-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-blue-500"
                  >
                  <button
                    type="button"
                    @click="show = !show"
                    class="absolute inset-y-0 right-0 mr-2 inline-flex items-center rounded-md px-2 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    x-bind:aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  >
                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12s3.5-7 9-7 9 7 9 7-3.5 7-9 7-9-7-9-7z" />
                      <circle cx="12" cy="12" r="3" stroke-width="1.8"></circle>
                    </svg>
                    <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0 1 12 19c-5.5 0-9-7-9-7a19.6 19.6 0 0 1-3.147 4.017M3 3l18 18" />
                    </svg>
                  </button>
                </div>
                @error('password')
                  <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
              </div>

              {{-- REMEMBER + registro general (opcional) --}}
              <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center gap-2">
                  <input id="remember_me" name="remember" type="checkbox"
                         class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                  <span class="text-sm text-slate-700">Recordarme</span>
                </label>

                @if (Route::has('register'))
                  <a href="{{ route('register') }}" class="text-sm text-slate-700 hover:text-slate-900">
                    Crear cuenta
                  </a>
                @endif
              </div>

              {{-- SUBMIT --}}
              <div class="pt-1">
                <button type="submit"
                        class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-slate-900 px-4 text-sm font-medium text-white shadow hover:bg-black focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                  Entrar
                </button>
              </div>
            </form>
          </div>

          {{-- separador sutil --}}
          <div class="my-6 divider"></div>

          {{-- CTA suscripción móvil (visible en pantallas pequeñas) --}}
          <div class="md:hidden">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
              <p class="text-sm text-slate-700">
                ¿Eres nuevo? <span class="font-medium">Registra tu empresa</span> o
                <span class="font-medium">únete a una empresa existente</span>.
              </p>
              <div class="mt-4 flex flex-wrap gap-3">
                @if (Route::has('register.company'))
                  <a href="{{ route('register.company') }}"
                     class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">
                    Registrar empresa
                  </a>
                @endif

                @if (Route::has('register.member'))
                  <a href="{{ route('register.member') }}"
                     class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100">
                    Unirme a una empresa
                  </a>
                @endif
              </div>
            </div>
          </div>

        </div>
      </section>
    </div>
  </main>
</body>
</html>
