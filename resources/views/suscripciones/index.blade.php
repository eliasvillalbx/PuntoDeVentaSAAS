<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">subscriptions</span>
        Suscripciones
      </h1>
      <a href="{{ route('suscripciones.create') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
        <span class="material-symbols-outlined mi text-base">add</span>
        Nueva suscripción
      </a>
    </div>
  </x-slot>

  @php
    $planLabel = [
      '1_mes'   => '1 mes',
      '6_meses' => '6 meses',
      '1_año'   => '1 año',
      '3_años'  => '3 años',
    ];
  @endphp

  <div class="max-w-7xl mx-auto space-y-6">
    {{-- Filtros --}}
    <form method="GET" action="{{ route('suscripciones.index') }}"
          class="bg-white rounded-xl border border-gray-200 p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div>
        <label class="block text-xs text-gray-600 mb-1">Empresa</label>
        <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="">Todas</option>
          @foreach ($empresas as $em)
            <option value="{{ $em->id }}" @selected(($qEmpresa ?? null) == $em->id)>{{ $em->razon_social }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Plan</label>
        <select name="plan" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          <option value="" @selected(($qPlan ?? '')==='')>Todos</option>
          @foreach (array_keys($planLabel) as $pl)
            <option value="{{ $pl }}" @selected(($qPlan ?? '')===$pl)>{{ $planLabel[$pl] }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-600 mb-1">Estado</label>
        @php $estados = ['', 'activa','vencida']; @endphp
        <select name="estado" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          @foreach ($estados as $es)
            <option value="{{ $es }}" @selected(($qEstado ?? '')===$es)>{{ $es === '' ? 'Todos' : ucfirst($es) }}</option>
          @endforeach
        </select>
      </div>
      <div class="lg:col-span-2">
        <label class="block text-xs text-gray-600 mb-1">Buscar por empresa</label>
        <input type="text" name="q" value="{{ $qTexto ?? '' }}" placeholder="Razón social…"
               class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
      </div>
      <div class="lg:col-span-5 flex gap-2 justify-end">
        <button type="submit" class="h-10 px-4 rounded-lg bg-gray-900 text-white text-sm">Aplicar</button>
        <a href="{{ route('suscripciones.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Limpiar</a>
      </div>
    </form>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-4 py-3 text-left">Empresa</th>
              <th class="px-4 py-3 text-left">Plan</th>
              <th class="px-4 py-3 text-left">Inicio</th>
              <th class="px-4 py-3 text-left">Vencimiento</th>
              <th class="px-4 py-3 text-left">Estado</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($suscripciones as $s)
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">{{ $s->empresa?->razon_social }}</td>
                <td class="px-4 py-3">{{ $planLabel[$s->plan] ?? $s->plan }}</td>
                <td class="px-4 py-3">{{ $s->fecha_inicio?->format('Y-m-d') }}</td>
                <td class="px-4 py-3">{{ $s->fecha_vencimiento?->format('Y-m-d') }}</td>
                <td class="px-4 py-3">
                  @php
                    $badge = $s->estado === 'activa'
                      ? 'bg-green-50 text-green-700 ring-green-200'
                      : 'bg-red-50 text-red-700 ring-red-200';
                  @endphp
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs ring-1 {{ $badge }}">
                    {{ ucfirst($s->estado) }}
                    @if($s->renovado)
                      <span class="ml-1 text-[10px] opacity-70">(renovada)</span>
                    @endif
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-end gap-1">
                    <a href="{{ route('suscripciones.show', $s) }}" class="px-2.5 py-1.5 rounded-lg hover:bg-gray-100" title="Ver">
                      <span class="material-symbols-outlined mi text-base">visibility</span>
                    </a>
                    <a href="{{ route('suscripciones.edit', $s) }}" class="px-2.5 py-1.5 rounded-lg hover:bg-gray-100" title="Editar">
                      <span class="material-symbols-outlined mi text-base">edit</span>
                    </a>

                    @if($s->estado === 'vencida' || $s->fecha_vencimiento->isPast())
                      <form method="POST" action="{{ route('suscripciones.renew', $s) }}" onsubmit="return confirm('¿Renovar suscripción?');">
                        @csrf
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg text-emerald-700 hover:bg-emerald-50" title="Renovar">
                          <span class="material-symbols-outlined mi text-base">autorenew</span>
                        </button>
                      </form>
                    @endif

                    <form method="POST" action="{{ route('suscripciones.destroy', $s) }}" onsubmit="return confirm('¿Eliminar suscripción?');">
                      @csrf @method('DELETE')
                      <button type="submit" class="px-2.5 py-1.5 rounded-lg text-red-700 hover:bg-red-50" title="Eliminar">
                        <span class="material-symbols-outlined mi text-base">delete</span>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">No hay suscripciones.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t border-gray-100 bg-white">
        {{ $suscripciones->onEachSide(1)->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
