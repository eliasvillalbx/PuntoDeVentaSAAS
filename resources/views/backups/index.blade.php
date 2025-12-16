<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-600">cloud_sync</span>
            {{ __('Copias de Seguridad y Restauración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ALERTAS --}}
            @if (session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-md shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                        <p class="font-semibold text-green-700">Operación exitosa</p>
                    </div>
                    <p class="text-green-600 text-sm mt-1">{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <p class="font-semibold text-red-700">Error del sistema</p>
                    </div>
                    <p class="text-red-600 text-sm mt-1">{{ session('error') }}</p>
                </div>
            @endif

            {{-- PANEL SUPERIOR DE ACCIONES --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Gestión de respaldos</h3>
                    <p class="text-sm text-gray-500">
                        Crea respaldos seguros de la base de datos o administra los existentes.
                    </p>
                </div>

                {{-- FORMULARIO CREAR CON AJAX --}}
                <form id="createBackupForm" action="{{ route('backups.create') }}" method="POST">
                    @csrf
                    <button
                        type="button" 
                        onclick="startBackupProcess()"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg shadow-md transition">
                        <span class="material-symbols-outlined">backup</span>
                        Crear respaldo
                    </button>
                </form>
            </div>

            {{-- NUEVO: FILTRO POR FECHA --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                <span class="material-symbols-outlined text-gray-400">filter_alt</span>
                <span class="text-sm font-semibold text-gray-700">Filtrar por fecha:</span>
                
                <input type="date" id="filterDate" onchange="filterTable()" 
                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                
                <button onclick="clearFilter()" 
                        class="text-sm text-indigo-600 hover:text-indigo-800 underline">
                    Ver todos
                </button>
            </div>

            {{-- TABLA --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if (count($backups))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="backupTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Archivo</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tamaño</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100" id="backupTableBody">
                                @foreach ($backups as $backup)
                                    <tr class="hover:bg-gray-50 transition backup-row">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                    <span class="material-symbols-outlined">folder_zip</span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800">{{ $backup['file_name'] }}</p>
                                                    <p class="text-xs text-gray-400 truncate max-w-xs">{{ $backup['file_path'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                {{ $backup['file_size'] }}
                                            </span>
                                        </td>
                                        {{-- IMPORTANTE: Aquí está la fecha que vamos a leer con JS --}}
                                        <td class="px-6 py-4 date-column">
                                            <p class="text-sm text-gray-800 date-text">{{ $backup['last_modified'] }}</p>
                                            <p class="text-xs text-gray-400">{{ $backup['ago'] }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-end gap-2">
                                                {{-- RESTAURAR --}}
                                                <form action="{{ route('backups.restore') }}" method="POST"
                                                      onsubmit="if(confirm('¿Restaurar BD?')) { showLoading('Restaurando...', 'No cierres la página.'); return true; } return false;">
                                                    @csrf
                                                    <input type="hidden" name="path" value="{{ $backup['file_path'] }}">
                                                    <button type="submit" class="p-2 rounded-full bg-amber-50 text-amber-600 hover:bg-amber-100 transition" title="Restaurar">
                                                        <span class="material-symbols-outlined text-lg">info</span>
                                                    </button>
                                                </form>

                                                {{-- DESCARGAR --}}
                                                <a href="{{ route('backups.download', ['path' => $backup['file_path']]) }}"
                                                   class="p-2 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition" title="Descargar">
                                                    <span class="material-symbols-outlined text-lg">download</span>
                                                </a>

                                                {{-- ELIMINAR --}}
                                                <form action="{{ route('backups.delete') }}" method="POST" onsubmit="return confirm('¿Eliminar?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="path" value="{{ $backup['file_path'] }}">
                                                    <button type="submit" class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition" title="Eliminar">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                {{-- Fila para mensaje "No encontrado" --}}
                                <tr id="noResultsRow" class="hidden">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No se encontraron respaldos en la fecha seleccionada.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- EMPTY STATE --}}
                    <div class="text-center py-16">
                        <span class="material-symbols-outlined text-6xl text-gray-300">cloud_off</span>
                        <h3 class="mt-4 text-lg font-semibold text-gray-800">No hay respaldos disponibles</h3>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- OVERLAY CON BARRA DE PROGRESO --}}
    <div id="loading-overlay" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-60 hidden backdrop-blur-sm transition-opacity">
        <div class="bg-white p-8 rounded-2xl shadow-2xl flex flex-col items-center gap-5 w-96 transform transition-all scale-100">
            <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-b-4 border-indigo-600 mb-2"></div>
            <div class="text-center w-full">
                <h3 class="text-xl font-bold text-gray-800 mb-1" id="loading-title">Procesando...</h3>
                <p class="text-gray-500 text-sm" id="loading-message">Por favor espera.</p>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden relative">
                <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 5%"></div>
            </div>
            <p class="text-xs text-gray-400 mt-[-10px]">No cierres esta ventana</p>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        // --- LÓGICA DE SPINNER Y REFRESH (Igual que antes) ---
        const initialBackupCount = {{ count($backups) }};

        function showLoading(title, message) {
            document.getElementById('loading-title').innerText = title;
            document.getElementById('loading-message').innerText = message;
            document.getElementById('loading-overlay').classList.remove('hidden');
            document.getElementById('loading-overlay').classList.add('flex');
        }

        function startBackupProcess() {
            showLoading('Generando Respaldo', 'Comprimiendo base de datos...');
            const form = document.getElementById('createBackupForm');
            const progressBar = document.getElementById('progress-bar');
            
            let progress = 5;
            const interval = setInterval(() => {
                if (progress < 90) {
                    progress += Math.random() * 5;
                    progressBar.style.width = progress + '%';
                }
            }, 500);

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                document.getElementById('loading-message').innerText = 'Finalizando archivo...';
                checkIfBackupExists(interval);
            })
            .catch(error => {
                alert('Error al iniciar respaldo.');
                location.reload();
            });
        }

        function checkIfBackupExists(progressInterval) {
            let attempts = 0;
            const checkLoop = setInterval(() => {
                attempts++;
                fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    // Buscamos filas con clase .backup-row para contar solo datos reales
                    const newCount = doc.querySelectorAll('.backup-row').length; 
                    
                    if (newCount > initialBackupCount) {
                        clearInterval(checkLoop);
                        clearInterval(progressInterval);
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('loading-message').innerText = '¡Listo! Recargando...';
                        setTimeout(() => { window.location.reload(); }, 500);
                    }
                });

                if (attempts > 30) {
                    clearInterval(checkLoop);
                    clearInterval(progressInterval);
                    alert('Tiempo de espera agotado. La página se recargará.');
                    window.location.reload();
                }
            }, 2000);
        }

        // --- NUEVA LÓGICA DE FILTRADO ---
        function filterTable() {
            const inputDate = document.getElementById('filterDate').value; // Formato yyyy-mm-dd
            const rows = document.querySelectorAll('.backup-row');
            let visibleCount = 0;

            rows.forEach(row => {
                // Obtenemos el texto de la fecha: "25/12/2024 14:30"
                const dateText = row.querySelector('.date-text').innerText.trim(); 
                
                // Convertimos "25/12/2024" a "2024-12-25" para comparar
                // [0] es el día, [1] el mes, [2] el año (tomando solo la parte antes del espacio)
                const fullDatePart = dateText.split(' ')[0]; // "25/12/2024"
                const parts = fullDatePart.split('/'); 
                
                // Formato ISO: AAAA-MM-DD
                const rowDateISO = `${parts[2]}-${parts[1]}-${parts[0]}`;

                if (inputDate === '' || rowDateISO === inputDate) {
                    row.style.display = ''; // Mostrar
                    visibleCount++;
                } else {
                    row.style.display = 'none'; // Ocultar
                }
            });

            // Manejar mensaje de "No hay resultados"
            const noResultsRow = document.getElementById('noResultsRow');
            if (visibleCount === 0 && rows.length > 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        function clearFilter() {
            document.getElementById('filterDate').value = '';
            filterTable();
        }
    </script>
</x-app-layout>