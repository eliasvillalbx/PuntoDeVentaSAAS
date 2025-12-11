{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Material Symbols --}}
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@0..1" rel="stylesheet" />
    <style>.mi{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}</style>

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  </head>

  @php
    /** @var \App\Models\User|null $u */
    $u = auth()->user();
    $isSuperAdmin = $u && method_exists($u, 'hasRole') ? $u->hasRole('superadmin') : false;

    // Empresa (usa accessor display_name y logo_url del modelo Empresa)
    $empresaNombre = !$isSuperAdmin ? (string) data_get($u, 'empresa.display_name', '') : '';
    $empresaLogo   = !$isSuperAdmin ? data_get($u, 'empresa.logo_url') : null;

    // Avatar de usuario
    $userAvatar = data_get($u, 'avatar_url');
    $userInitials = function($name) {
        $parts = preg_split('/\s+/', trim($name ?? '')); $ini = '';
        foreach ($parts as $p) {
            if ($p !== '') {
                $ini .= mb_strtoupper(mb_substr($p,0,1));
            }
            if (mb_strlen($ini) >= 2) break;
        }
        return $ini !== '' ? $ini : 'U';
    };
  @endphp

  <body class="font-sans antialiased bg-gray-100"
        x-data="layoutState()"
        x-init="init()">

    {{-- TOASTS (bottom) --}}
    <div class="fixed inset-x-0 bottom-0 z-[60] flex justify-center pointer-events-none px-4 sm:px-6 lg:px-8">
      <div class="w-full max-w-3xl mb-6 space-y-2 pointer-events-auto">
        @if (session('success'))
          <div x-data="{show:true}" x-init="setTimeout(()=>show=false,3500)" x-show="show" x-transition
               class="rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3 flex items-start gap-2">
            <span class="material-symbols-outlined mi">check_circle</span>
            <div>{{ session('success') }}</div>
          </div>
        @endif

        @if (session('error'))
          <div x-data="{show:true}" x-init="setTimeout(()=>show=false,5000)" x-show="show" x-transition
               class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3 flex items-start gap-2">
            <span class="material-symbols-outlined mi">error</span>
            <div>{{ session('error') }}</div>
          </div>
        @endif

        @if ($errors->any())
          <div x-data="{show:true}" x-init="setTimeout(()=>show=false,5000)" x-show="show" x-transition
               class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3 flex items-start gap-2">
            <span class="material-symbols-outlined mi">warning</span>
            <div>{{ $errors->first() }}</div>
          </div>
        @endif

        @if (session('info'))
          <div x-data="{show:true}" x-init="setTimeout(()=>show=false,3500)" x-show="show" x-transition
               class="rounded-lg bg-blue-50 text-blue-800 ring-1 ring-blue-200 p-3 flex items-start gap-2">
            <span class="material-symbols-outlined mi">info</span>
            <div>{{ session('info') }}</div>
          </div>
        @endif
      </div>
    </div>

    <div class="min-h-screen">
      {{-- NAVBAR --}}
      @include('layouts.navbar', [
        'u'             => $u,
        'isSuperAdmin'  => $isSuperAdmin,
        'empresaNombre' => $empresaNombre,
        'empresaLogo'   => $empresaLogo,
        'userAvatar'    => $userAvatar,
        'userInitials'  => $userInitials,
      ])

      <div class="flex">
        {{-- SIDEBAR --}}
        @include('layouts.sidebar')

        {{-- CONTENIDO --}}
        <main class="flex-1 min-h-[calc(100vh-4rem)]">
          @isset($header)
            <header class="bg-white shadow">
              <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
              </div>
            </header>
          @endisset

          <div class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
          </div>
        </main>
      </div>
    </div>

    {{-- WIDGET GLOBAL DE CHAT (solo para usuarios autenticados) --}}
    @auth
      @include('chat.widget')
    @endauth

    {{-- Alpine helpers --}}
    <script>
      function layoutState() {
        return {
          sidebarOpen: true,
          profileOpen: false,

          init() {
            const saved = localStorage.getItem('sidebarOpen');
            if (saved !== null) {
              try { this.sidebarOpen = JSON.parse(saved); } catch(_) {}
            }
            window.addEventListener('keydown', e => {
              if (e.key === 'Escape') this.profileOpen = false;
            });
          },

          toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
            localStorage.setItem('sidebarOpen', JSON.stringify(this.sidebarOpen));
          },
        }
      }
    </script>
  </body>
</html>
