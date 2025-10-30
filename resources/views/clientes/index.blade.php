<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">group</span>
        Clientes
      </h1>
      <a href="{{ route('clientes.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nuevo
      </a>
    </div>
  </x-slot>

  <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

    @if (session('status'))
      <div class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">
        <ul class="list-disc pl-6">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    @endif

    @php $isSA = auth()->user()->hasRole('superadmin'); @endphp

    <form method="GET" class="bg-white border rounded-xl shadow-sm p-4 mb-6 grid grid-cols-1 md:grid-cols-7 gap-3">
      @if($isSA)
        <div class="md:col-span-2">
          <label class="text-xs text-gray-600">Empresa</label>
          <select name="empresa_id" class="mt-1 w-full rounded-md border-gray-300">
            <option value="">Todas</option>
            @foreach($empresas as $em)
              <option value="{{ $em->id }}" @selected((int)request('empresa_id') === (int)$em->id)>
                {{ $em->nombre_comercial ?? $em->razon_social }}
              </option>
            @endforeach
          </select>
        </div>
      @endif

      <div class="{{ $isSA ? 'md:col-span-2' : 'md:col-span-3' }}">
        <label class="text-xs text-gray-600">Buscar</label>
        <input type="text" name="q" value="{{ $q }}" class="mt-1 w-full rounded-md border-gray-300" placeholder="Nombre, razón social, RFC, email...">
      </div>

      <div>
        <label class="text-xs text-gray-600">Tipo</label>
        <select name="tipo_persona" class="mt-1 w-full rounded-md border-gray-300">
          <option value="">Todos</option>
          <option value="fisica" @selected(request('tipo_persona')==='fisica')>Física</option>
          <option value="moral" @selected(request('tipo_persona')==='moral')>Moral</option>
        </select>
      </div>

      <div>
        <label class="text-xs text-gray-600">Activo</label>
        <select name="activo" class="mt-1 w-full rounded-md border-gray-300">
          <option value="">Todos</option>
          <option value="1" @selected(request('activo')==='1')>Sí</option>
          <option value="0" @selected(request('activo')==='0')>No</option>
        </select>
      </div>

      <div class="md:col-span-1 flex items-end">
        <button class="w-full h-10 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black">Filtrar</button>
      </div>
    </form>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-600">
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Nombre/Razón social</th>
            <th class="px-4 py-3 text-left">RFC</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Teléfono</th>
            <th class="px-4 py-3 text-left">Activo</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($clientes as $c)
          <tr class="text-sm">
            <td class="px-4 py-3">#{{ $c->id }}</td>
            <td class="px-4 py-3 font-medium">{{ $c->nombre_mostrar }}</td>
            <td class="px-4 py-3">{{ $c->rfc ?? '—' }}</td>
            <td class="px-4 py-3">{{ $c->email ?? '—' }}</td>
            <td class="px-4 py-3">{{ $c->telefono ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $c->activo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                {{ $c->activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex gap-2 justify-end">
                <a href="{{ route('clientes.show', $c) }}" class="text-indigo-600 hover:underline">Ver</a>
                <a href="{{ route('clientes.edit', $c) }}" class="text-gray-700 hover:underline">Editar</a>
                <form action="{{ route('clientes.destroy', $c) }}" method="POST" onsubmit="return confirm('¿Eliminar cliente?');">
                  @csrf @method('DELETE')
                  <button class="text-red-600 hover:underline">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">Sin registros.</td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-4 py-3">
        {{ $clientes->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
