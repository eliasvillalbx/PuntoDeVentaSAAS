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

            {{-- PANEL SUPERIOR --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Gestión de respaldos</h3>
                    <p class="text-sm text-gray-500">
                        Crea respaldos seguros de la base de datos o administra los existentes.
                    </p>
                </div>

                <form action="{{ route('backups.create') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg shadow-md transition">
                        <span class="material-symbols-outlined">backup</span>
                        Crear respaldo
                    </button>
                </form>
            </div>

            {{-- TABLA --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @if (count($backups))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Archivo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Tamaño
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">
                                        Fecha
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @foreach ($backups as $backup)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                    <span class="material-symbols-outlined">folder_zip</span>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800">
                                                        {{ $backup['file_name'] }}
                                                    </p>
                                                    <p class="text-xs text-gray-400 truncate max-w-xs">
                                                        {{ $backup['file_path'] }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                                {{ $backup['file_size'] }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <p class="text-sm text-gray-800">
                                                {{ $backup['last_modified'] }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                {{ $backup['ago'] }}
                                            </p>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="flex justify-end gap-2">

                                                {{-- RESTAURAR (INFO) --}}
                                                <form action="{{ route('backups.restore') }}" method="POST"
                                                      onsubmit="return confirm('Por seguridad, la restauración se realiza de forma manual.\n\n¿Deseas continuar?');">
                                                    @csrf
                                                    <input type="hidden" name="path" value="{{ $backup['file_path'] }}">
                                                    <button
                                                        type="submit"
                                                        title="Restauración manual"
                                                        class="p-2 rounded-full bg-amber-50 text-amber-600 hover:bg-amber-100 transition">
                                                        <span class="material-symbols-outlined text-lg">info</span>
                                                    </button>
                                                </form>

                                                {{-- DESCARGAR --}}
                                                <a href="{{ route('backups.download', ['path' => $backup['file_path']]) }}"
                                                   title="Descargar respaldo"
                                                   class="p-2 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                                    <span class="material-symbols-outlined text-lg">download</span>
                                                </a>

                                                {{-- ELIMINAR --}}
                                                <form action="{{ route('backups.delete') }}" method="POST"
                                                      onsubmit="return confirm('¿Eliminar este respaldo de forma permanente?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="path" value="{{ $backup['file_path'] }}">
                                                    <button
                                                        type="submit"
                                                        title="Eliminar respaldo"
                                                        class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition">
                                                        <span class="material-symbols-outlined text-lg">delete</span>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- EMPTY STATE --}}
                    <div class="text-center py-16">
                        <span class="material-symbols-outlined text-6xl text-gray-300">cloud_off</span>
                        <h3 class="mt-4 text-lg font-semibold text-gray-800">
                            No hay respaldos disponibles
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Aún no se ha generado ningún respaldo de la base de datos.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
