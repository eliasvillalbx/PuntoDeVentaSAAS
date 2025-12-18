<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión — POS Empresarial</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- 1. IMPORTANTE: Cargar Iconos --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

  {{-- Tailwind + Alpine --}}
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script defer src="https://unpkg.com/alpinejs"></script>

  <style>
    .card { background: #ffffff; }
    .divider { height:1px; background:linear-gradient(90deg,rgba(0,0,0,.06),rgba(0,0,0,.02),rgba(0,0,0,.06)); }
    /* Ajuste para alinear iconos verticalmente */
    .material-symbols-outlined { font-size: 20px; vertical-align: bottom; }
  </style>
</head>
<body class="h-full antialiased text-slate-800">
  <main class="min-h-full">
    <div class="mx-auto flex min-h-screen max-w-7xl flex-col md:flex-row">
      
      {{-- ================= Columna Izquierda (Branding) ================= --}}
      <section class="hidden md:flex md:w-1/2 items-center justify-center border-r border-slate-200/80 px-10 bg-slate-50/30">
        <div class="w-full max-w-md">
          
          <div class="mb-10">
            {{-- NUEVO LOGO --}}
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
            Gestiona tu negocio<br>con inteligencia.
          </h1>
          <p class="mt-4 text-slate-600 text-lg leading-relaxed">
            Tu plataforma central para ventas, inventarios y clientes. Todo sincronizado en tiempo real.
          </p>

          {{-- Mensaje de bienvenida / info --}}
          <div class="mt-10 border-l-4 border-slate-900 pl-4">
             <p class="text-sm font-medium text-slate-900">¿Eres nuevo aquí?</p>
             <p class="text-sm text-slate-600 mt-1">
                Solicita a tu administrador el código de acceso o registra tu empresa si eres el propietario.
             </p>
          </div>

          {{-- Botones de registro rápido --}}
          <div class="mt-8 flex flex-wrap gap-3">
             @if (Route::has('register.company'))
                <a href="{{ route('register.company') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                   <span class="material-symbols-outlined text-slate-500">domain_add</span>
                   Registrar empresa
                </a>
             @endif

             @if (Route::has('register.member'))
                <a href="{{ route('register.member') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                   <span class="material-symbols-outlined text-slate-500">group_add</span>
                   Unirme a equipo
                </a>
             @endif
          </div>
        </div>
      </section>

      {{-- ================= Columna Derecha (Login Form) ================= --}}
      <section class="flex w-full md:w-1/2 items-center justify-center px-6 sm:px-8 py-10">
        <div class="w-full max-w-md">
            
          {{-- Logo visible solo en móvil --}}
          <div class="md:hidden mb-8 text-center">
             <span class="text-2xl font-black text-slate-900 tracking-tighter">POS</span>
             <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Empresarial</span>
          </div>

          <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-900">Bienvenido de nuevo</h2>
            <p class="mt-2 text-sm text-slate-600">Ingresa tus credenciales para acceder al panel.</p>
          </div>

          {{-- Mensajes de estado --}}
          @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
               <span class="material-symbols-outlined">check_circle</span>
               <span>{{ session('status') }}</span>
            </div>
          @endif

          @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              <div class="font-bold flex items-center gap-2 mb-1">
                 <span class="material-symbols-outlined text-red-600">error</span>
                 Error de acceso
              </div>
              <ul class="list-disc pl-8 space-y-1">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="card rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-sm">
            <form method="POST" action="{{ route('login') }}" x-data="{ show: false }" novalidate class="space-y-5">
              @csrf

              {{-- EMAIL --}}
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
                      inputmode="email"
                      required
                      value="{{ old('email') }}"
                      placeholder="nombre@empresa.com"
                      class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-3 py-2.5 text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all sm:text-sm"
                    >
                </div>
              </div>

              {{-- PASSWORD --}}
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <label for="password" class="block text-sm font-semibold text-slate-700">Contraseña</label>
                  @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs font-medium text-slate-500 hover:text-slate-800 hover:underline">
                      ¿Olvidaste tu contraseña?
                    </a>
                  @endif
                </div>
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-slate-400">lock</span>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        placeholder="••••••••"
                        x-bind:type="show ? 'text' : 'password'"
                        class="block w-full rounded-lg border border-slate-300 bg-white pl-10 pr-10 py-2.5 text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all sm:text-sm"
                    >
                    <button
                        type="button"
                        @click="show = !show"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                    >
                        <span class="material-symbols-outlined" x-text="show ? 'visibility_off' : 'visibility'"></span>
                    </button>
                </div>
              </div>

              {{-- REMEMBER ME --}}
              <div class="flex items-center">
                <input id="remember_me" name="remember" type="checkbox"
                       class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500 cursor-pointer">
                <label for="remember_me" class="ml-2 block text-sm text-slate-600 cursor-pointer select-none">
                  Mantener sesión iniciada
                </label>
              </div>

              {{-- SUBMIT --}}
              <button type="submit"
                      class="w-full flex justify-center items-center gap-2 py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all">
                Ingresar al sistema
                <span class="material-symbols-outlined text-[18px]">login</span>
              </button>
            </form>
          </div>
          
          <p class="mt-6 text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} POS Empresarial. Todos los derechos reservados.
          </p>

        </div>
      </section>
    </div>
  </main>
</body>
</html>