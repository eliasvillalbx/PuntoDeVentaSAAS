<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restablecer Contraseña — POS Empresarial</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- 1. Cargar Iconos --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  {{-- Tailwind + Alpine --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script defer src="https://unpkg.com/alpinejs"></script>

  <style>
    .card { background: #ffffff; }
    /* Ajuste de iconos */
    .material-symbols-outlined { font-size: 20px; vertical-align: bottom; }
  </style>
</head>
<body class="h-full antialiased text-slate-800">
  <main class="min-h-full">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col md:flex-row">
      
      {{-- ================= Columna Izquierda (Branding / Tips) ================= --}}
      <section class="hidden md:flex md:w-1/2 items-center justify-center border-r border-slate-200/80 px-10 bg-slate-50/30">
        <div class="w-full max-w-md">
          
          <div class="mb-10">
            {{-- LOGO --}}
            <div class="flex items-center gap-3 select-none">
                <div class="inline-flex flex-col items-start gap-0">
                    <span class="text-3xl font-black text-slate-900 leading-none tracking-tighter">
                        POS
                    </span>
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.25em] ml-0.5">
                        Empresarial
                    </span>
                </div>
            </div>
          </div>

          <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            Seguridad ante todo.
          </h1>
          <p class="mt-4 text-slate-600 text-lg leading-relaxed">
            Estás a un paso de recuperar el acceso a tu cuenta. Por favor, crea una nueva credencial segura.
          </p>

          {{-- Tip de Seguridad --}}
          <div class="mt-8 rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">
             <div class="flex items-start gap-3">
                 <span class="material-symbols-outlined text-blue-600 mt-0.5">shield_lock</span>
                 <div>
                     <h3 class="text-sm font-bold text-slate-900">Recomendación</h3>
                     <p class="mt-1 text-sm text-slate-600 leading-relaxed">
                        Usa al menos 8 caracteres, combinando letras mayúsculas, números y símbolos para proteger mejor los datos de tu empresa.
                     </p>
                 </div>
             </div>
          </div>

        </div>
      </section>

      {{-- ================= Columna Derecha (Formulario Reset) ================= --}}
      <section class="flex w-full md:w-1/2 items-center justify-center px-6 sm:px-8 py-10">
        <div class="w-full max-w-md">
            
          <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Crear nueva contraseña</h2>
            <p class="mt-2 text-sm text-slate-600">Ingresa y confirma tu nueva clave de acceso.</p>
          </div>

          {{-- Errores --}}
          @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              <div class="font-bold flex items-center gap-2 mb-1">
                 <span class="material-symbols-outlined text-red-600">error</span>
                 Atención
              </div>
              <ul class="list-disc pl-8 space-y-1">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="card rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            {{-- Usamos showPass y showConf para manejar los ojitos independientemente --}}
            <form method="POST" action="{{ route('password.store') }}" x-data="{ showPass: false, showConf: false }" class="space-y-5">
              @csrf

              <input type="hidden" name="token" value="{{ $request->route('token') }}">

              {{-- EMAIL (Readonly recomendado para que sepan qué cuenta es) --}}
              <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo electrónico</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-slate-400">mail</span>
                    </div>
                    <input
                      id="email"
                      name="email"
                      type="email"
                      required
                      readonly
                      value="{{ old('email', $request->email) }}"
                      class="block w-full rounded-lg border border-slate-300 bg-slate-50 pl-10 pr-3 py-2.5 text-slate-500 cursor-not-allowed sm:text-sm"
                    >
                </div>
              </div>

              {{-- NUEVA CONTRASEÑA --}}
              <div>
                <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Nueva contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-slate-400">key</span>
                    </div>
                    <input
                        id="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Mínimo 8 caracteres"
                        x-bind:type="showPass ? 'text' : 'password'"
                        class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 py-2.5 text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all sm:text-sm"
                    >
                    <button
                        type="button"
                        @click="showPass = !showPass"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    >
                        <span class="material-symbols-outlined" x-text="showPass ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
              </div>

              {{-- CONFIRMAR CONTRASEÑA --}}
              <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-slate-400">lock_reset</span>
                    </div>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Repite la contraseña"
                        x-bind:type="showConf ? 'text' : 'password'"
                        class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 py-2.5 text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all sm:text-sm"
                    >
                    <button
                        type="button"
                        @click="showConf = !showConf"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    >
                        <span class="material-symbols-outlined" x-text="showConf ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
              </div>

              {{-- SUBMIT --}}
              <div class="pt-2">
                  <button type="submit"
                          class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all">
                    Restablecer contraseña
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                  </button>
              </div>
            </form>
          </div>
          
        </div>
      </section>
    </div>
  </main>
</body>
</html>