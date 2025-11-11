<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit_note</span>
        Editar compra #{{ $compra->id }}
      </h1>
      <a href="{{ route('compras.show', $compra->id) }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto space-y-6" x-data="formCompra()"
       x-init='init(@json($productos), @json($proveedores), @json($prefill));
               $nextTick(()=>{ proveedor_id = "{{ $compra->id_proveedor }}"; })'>

    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('compras.update', $compra->id) }}" class="bg-white rounded-xl border p-6 space-y-6">
      @csrf @method('PUT')

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
          <label class="text-sm text-gray-600">Proveedor</label>
          <select name="id_proveedor" x-model="proveedor_id" class="w-full rounded-lg border-gray-300" required>
            <option value="">Selecciona…</option>
            <template x-for="p in proveedores" :key="p.id">
              <option :value="p.id" x-text="p.nombre"></option>
            </template>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Fecha</label>
          <input type="date" name="fecha_compra" class="w-full rounded-lg border-gray-300" value="{{ \Illuminate\Support\Carbon::parse($compra->fecha_compra)->toDateString() }}" required>
        </div>
        <div>
          <label class="text-sm text-gray-600">Estatus</label>
          <select name="estatus" class="w-full rounded-lg border-gray-300" required>
            @foreach (['borrador','orden_compra','recibida','cancelada'] as $st)
              <option value="{{ $st }}" @selected($compra->estatus===$st)>{{ ucfirst(str_replace('_',' ', $st)) }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-gray-600">Observaciones</label>
        <textarea name="observaciones" rows="2" class="w-full rounded-lg border-gray-300">{{ $compra->observaciones }}</textarea>
      </div>

      {{-- Tabla items (mismo script que create) --}}
      @include('compras.partials.items-table')

      <div class="flex items-center justify-end gap-3">
        <button class="h-10 px-4 rounded-xl bg-gray-800 text-white hover:bg-gray-900">Actualizar</button>
      </div>
    </form>
  </div>

  @include('compras.partials.items-script')
</x-app-layout>
