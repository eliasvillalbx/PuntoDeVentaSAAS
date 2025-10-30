<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit</span>
        Editar venta #{{ $venta->id }}
      </h1>
      <a href="{{ route('ventas.show', $venta) }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">visibility</span>
        Ver detalle
      </a>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
      @endif
      @if (session('status'))
        <div class="mb-4 rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('status') }}</div>
      @endif

      <form method="POST" action="{{ route('ventas.update', $venta) }}" x-data="ventaEdit()"
            class="space-y-8">
        @csrf @method('PUT')

        {{-- Empresa (solo lectura) --}}
        <section class="space-y-3">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">domain</span>
            Empresa
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
              <input type="text" value="{{ $venta->empresa?->nombre_comercial ?? $venta->empresa?->razon_social ?? ('Empresa #'.$venta->empresa_id) }}"
                     class="w-full h-10 rounded-lg border-gray-200 bg-gray-50 text-sm" readonly>
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Responsable</label>
              <select name="usuario_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
                @foreach ($responsables as $u)
                  <option value="{{ $u->id }}" @selected(old('usuario_id',$venta->usuario_id) == $u->id)>
                    {{ $u->nombre }} {{ $u->apellido_paterno }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </section>

        {{-- Datos --}}
        <section class="space-y-3">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">receipt</span>
            Datos
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Fecha <span class="text-red-600">*</span></label>
              <input type="date" name="fecha_venta" value="{{ old('fecha_venta', \Illuminate\Support\Carbon::parse($venta->fecha_venta)->toDateString()) }}"
                     class="w-full h-10 rounded-lg border-gray-300 text-sm" required>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Cliente</label>
              <select name="cliente_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
                <option value="">Venta directa</option>
                @foreach ($clientes as $c)
                  <option value="{{ $c->id }}" @selected(old('cliente_id',$venta->cliente_id)==$c->id)>{{ $c->nombre ?? $c->razon_social }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Estatus <span class="text-red-600">*</span></label>
              <select name="estatus" class="w-full h-10 rounded-lg border-gray-300 text-sm" x-model="estatus" required>
                @foreach (['borrador','prefactura','facturada','cancelada'] as $st)
                  <option value="{{ $st }}" @selected(old('estatus',$venta->estatus)===$st)>{{ ucfirst($st) }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('observaciones', $venta->observaciones) }}</textarea>
          </div>
        </section>

        {{-- Productos --}}
        <section class="space-y-3">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">inventory_2</span>
            Productos
          </h2>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left font-semibold text-gray-700">Producto</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-700">Cantidad</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-700">P. Unitario</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-700">Descuento</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-700">Total</th>
                <th class="px-3 py-2"></th>
              </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <template x-for="(row, idx) in rows" :key="idx">
                  <tr>
                    <td class="px-3 py-2">
                      <select :name="`items[${idx}][producto_id]`" x-model.number="row.producto_id"
                              class="w-full h-10 rounded-lg border-gray-300">
                        <option value="">Selecciona…</option>
                        @foreach ($productos as $p)
                          <option value="{{ $p->id }}">{{ $p->nombre }} — ${{ number_format($p->precio,2) }} (Stock: {{ $p->stock }})</option>
                        @endforeach
                      </select>
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" step="0.01" min="0.01" x-model.number="row.cantidad"
                             :name="`items[${idx}][cantidad]`"
                             class="w-28 h-10 rounded-lg border-gray-300 text-right">
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" step="0.01" min="0" x-model.number="row.precio_unitario"
                             :name="`items[${idx}][precio_unitario]`"
                             class="w-28 h-10 rounded-lg border-gray-300 text-right">
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" step="0.01" min="0" x-model.number="row.descuento"
                             :name="`items[${idx}][descuento]`"
                             class="w-28 h-10 rounded-lg border-gray-300 text-right">
                    </td>
                    <td class="px-3 py-2 text-right" x-text="formatMoney(lineTotal(row))"></td>
                    <td class="px-3 py-2 text-right">
                      <button type="button" @click="removeRow(idx)" class="text-gray-500 hover:text-red-600">
                        <span class="material-symbols-outlined mi">delete</span>
                      </button>
                    </td>
                  </tr>
                </template>
              </tbody>
              <tfoot class="bg-gray-50">
              <tr>
                <td colspan="6" class="px-3 py-2">
                  <button type="button" @click="addRow()" class="inline-flex items-center gap-2 h-9 px-3 rounded-lg bg-gray-800 text-white text-xs">
                    <span class="material-symbols-outlined mi text-sm">add</span> Agregar producto
                  </button>
                </td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td class="px-3 py-2 text-right font-medium">Subtotal</td>
                <td class="px-3 py-2 text-right" x-text="formatMoney(subtotal())"></td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td class="px-3 py-2 text-right font-medium">IVA (16%)</td>
                <td class="px-3 py-2 text-right" x-text="formatMoney(iva())"></td>
                <td></td>
              </tr>
              <tr>
                <td colspan="3"></td>
                <td class="px-3 py-2 text-right font-semibold">Total</td>
                <td class="px-3 py-2 text-right font-semibold" x-text="formatMoney(total())"></td>
                <td></td>
              </tr>
              </tfoot>
            </table>
          </div>
        </section>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('ventas.show', $venta) }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span>
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
            <span class="material-symbols-outlined mi text-base">save</span>
            Actualizar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Alpine helpers --}}
  <script>
    function ventaEdit() {
      const initial = @json($venta->detalle->map(fn($d)=>[
        'producto_id' => (int)$d->producto_id,
        'cantidad' => (float)$d->cantidad,
        'precio_unitario' => (float)$d->precio_unitario,
        'descuento' => (float)($d->descuento ?? 0),
      ]));
      return {
        estatus: @json($venta->estatus),
        rows: initial.length ? initial : [{ producto_id:'', cantidad:1, precio_unitario:0, descuento:0 }],
        addRow() { this.rows.push({ producto_id:'', cantidad:1, precio_unitario:0, descuento:0 }); },
        removeRow(i) { this.rows.splice(i, 1); },
        lineTotal(r) { const t = (r.cantidad * r.precio_unitario) - (r.descuento || 0); return t > 0 ? t : 0; },
        subtotal() { return this.rows.reduce((s, r) => s + this.lineTotal(r), 0); },
        iva() { return +(this.subtotal() * 0.16).toFixed(2); },
        total() { return +(this.subtotal() + this.iva()).toFixed(2); },
        formatMoney(n) { return (n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' }); }
      }
    }
  </script>
</x-app-layout>
