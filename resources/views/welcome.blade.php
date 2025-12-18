<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Empresarial — Gestión Inteligente</title>

    {{-- Fuentes e Iconos --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    {{-- Tailwind + Alpine (CDN para desarrollo rápido, usa @vite en producción) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        slate: { 850: '#1e293b' } // Un tono más oscuro personalizado
                    }
                }
            }
        }
    </script>

    <style>
        .pattern-dots {
            background-image: radial-gradient(#cbd5e1 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        }
        .text-gradient {
            background: linear-gradient(to right, #4f46e5, #9333ea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="antialiased text-slate-800 bg-white">

    {{-- ===================== NAVBAR ===================== --}}
    <header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-200/60 transition-all duration-300" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                {{-- Logo --}}
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-1.5 rounded-lg text-white shadow-lg shadow-indigo-500/30">
                        <span class="material-symbols-outlined text-[24px]">point_of_sale</span>
                    </div>
                    <div class="flex flex-col leading-none">
                        <span class="text-xl font-black text-slate-900 tracking-tight">POS</span>
                        <span class="text-[9px] font-bold text-indigo-600 uppercase tracking-widest">Empresarial</span>
                    </div>
                </div>

                {{-- Navegación Derecha --}}
                <nav class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors">
                                Ir al Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:block text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                                Iniciar Sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-2 text-sm font-semibold text-white transition-all duration-200 bg-slate-900 rounded-full hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                                    Comenzar Gratis
                                </a>
                            @endif
                        @endauth
                    @endif
                </nav>
            </div>
        </div>
    </header>

    <main>
        {{-- ===================== HERO SECTION ===================== --}}
        <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
            <div class="absolute inset-0 -z-10 h-full w-full bg-slate-50 pattern-dots opacity-60"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-white to-transparent"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider mb-8 animate-fade-in-up">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                    Versión 1.0 
                </div>
                
                <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 mb-6 leading-tight">
                    Gestiona tu negocio <br>
                    <span class="text-gradient">con inteligencia real.</span>
                </h1>
                
                <p class="mt-4 max-w-2xl mx-auto text-xl text-slate-600 leading-relaxed">
                    Deja de adivinar. Obtén reportes de rentabilidad precisa, controla tu inventario con costos reales y gestiona múltiples sucursales en una sola plataforma SaaS.
                </p>

                <div class="mt-10 flex justify-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="px-8 py-4 text-base font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-500 shadow-xl shadow-indigo-500/20 transition-all transform hover:-translate-y-1">
                            Entrar al Sistema
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="px-8 py-4 text-base font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 shadow-xl shadow-slate-900/20 transition-all transform hover:-translate-y-1">
                            Registrar mi Empresa
                        </a>
                        <a href="#features" class="px-8 py-4 text-base font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Ver Funciones
                        </a>
                    @endauth
                </div>

                {{-- Dashboard Preview (Mockup) --}}
                <div class="mt-16 relative mx-auto max-w-5xl rounded-2xl border border-slate-200 bg-white/50 backdrop-blur-sm p-2 shadow-2xl lg:mt-24">
                    <div class="rounded-xl overflow-hidden bg-slate-100 border border-slate-200 aspect-[16/9] flex items-center justify-center relative">
                        {{-- Aquí iría una imagen real de tu dashboard --}}
                        <div class="text-center">
                            <span class="material-symbols-outlined text-6xl text-slate-300 mb-2">analytics</span>
                            <p class="text-slate-400 font-medium">Vista Previa del Dashboard de Analítica</p>
                        </div>
                        
                        {{-- Floating Badge 1 --}}
                        <div class="absolute -right-4 top-10 bg-white p-4 rounded-lg shadow-lg border border-slate-100 animate-bounce" style="animation-duration: 3s;">
                            <div class="flex items-center gap-3">
                                <div class="bg-emerald-100 p-2 rounded-full text-emerald-600"><span class="material-symbols-outlined text-lg">trending_up</span></div>
                                <div>
                                    <p class="text-xs text-slate-500 uppercase font-bold">Rentabilidad</p>
                                    <p class="text-lg font-bold text-slate-900">+24%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== FEATURES GRID ===================== --}}
        <section id="features" class="py-24 bg-white relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-base font-bold text-indigo-600 uppercase tracking-wide">Características Potentes</h2>
                    <p class="mt-2 text-3xl font-bold text-slate-900 sm:text-4xl">Todo lo que necesitas para escalar.</p>
                    <p class="mt-4 text-lg text-slate-600">Diseñado para gerentes exigentes. No solo registramos ventas, analizamos tu crecimiento.</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    {{-- Feature 1 --}}
                    <div class="group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-indigo-100 hover:shadow-lg hover:shadow-indigo-500/5 transition-all duration-300">
                        <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-white mb-6 shadow-md shadow-indigo-500/20 group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl">query_stats</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Rentabilidad Real</h3>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            Calculamos la ganancia exacta cruzando tus ventas con el historial de costos de compra y proveedores. Adiós a las estimaciones.
                        </p>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-100 hover:shadow-lg hover:shadow-emerald-500/5 transition-all duration-300">
                        <div class="w-12 h-12 bg-emerald-600 rounded-xl flex items-center justify-center text-white mb-6 shadow-md shadow-emerald-500/20 group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl">inventory_2</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Inventario Valorizado</h3>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            Conoce cuánto vale tu stock al centavo. Usamos lógica de cascada (Compra > Proveedor > Referencia) para una valoración precisa.
                        </p>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-100 hover:shadow-lg hover:shadow-amber-500/5 transition-all duration-300">
                        <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center text-white mb-6 shadow-md shadow-amber-500/20 group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-2xl">cloud_sync</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Modelo SaaS & Nube</h3>
                        <p class="text-slate-600 leading-relaxed text-sm">
                            Gestiona múltiples sucursales y suscripciones. Tu información segura, respaldada y accesible desde cualquier lugar del mundo.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===================== CTA FINAL ===================== --}}
        <section class="py-20 bg-slate-900 text-white relative overflow-hidden">
            {{-- Decoration --}}
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-indigo-600 rounded-full blur-[100px] opacity-30"></div>
            <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-purple-600 rounded-full blur-[100px] opacity-30"></div>

            <div class="max-w-4xl mx-auto px-4 relative z-10 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-6">¿Listo para tomar el control?</h2>
                <p class="text-slate-300 text-lg mb-10 max-w-2xl mx-auto">
                    Únete a las empresas que ya están optimizando sus procesos con POS Empresarial.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-white text-slate-900 font-bold rounded-xl hover:bg-slate-100 transition-colors">
                        Crear Cuenta Gratis
                    </a>
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-transparent border border-slate-600 text-white font-bold rounded-xl hover:bg-slate-800 transition-colors">
                        Ya tengo cuenta
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-slate-200 pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400">point_of_sale</span>
                <span class="font-bold text-slate-700">POS Empresarial</span>
            </div>
            <p class="text-slate-500 text-sm">
                &copy; {{ date('Y') }} Todos los derechos reservados.
            </p>
        </div>
    </footer>

</body>
</html>