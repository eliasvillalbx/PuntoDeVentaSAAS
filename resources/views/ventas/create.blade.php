<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">point_of_sale</span>
        Nueva venta / prefactura
      </h1>
      <a href="{{ route('ventas.index', ['empresa_id' => $empresaId]) }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
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

      @php $authUser = auth()->user(); @endphp

      <form method="POST" action="{{ route('ventas.store') }}" x-data="ventaItems()"
            class="space-y-8">
        @csrf

        {{-- Empresa (SA) / hidden (no-SA) --}}
        @if ($isSA)
          <section class="space-y-3">
            <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
              <span class="material-symbols-outlined mi text-base">domain</span>
              Empresa
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="sm:col-span-2">
                <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
                <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 text-sm" required>
                  <option value="">Selecciona empresa…</option>
                  @foreach ($empresas as $em)
                    <option value="{{ $em->id }}" @selected(old('empresa_id', $empresaId) == $em->id)>
                      {{ $em->nombre_comercial ?? $em->razon_social }}
                    </option>
                  @endforeach
                </select>
                @error('empresa_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Responsable</label>
                <select name="usuario_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
                  @foreach ($responsables as $u)
                    <option value="{{ $u->id }}" @selected(old('usuario_id') == $u->id)>
                      {{ $u->nombre }} {{ $u->apellido_paterno }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>
          </section>
        @else
          <input type="hidden" name="empresa_id" value="{{ $authUser->id_empresa }}">
          <input type="hidden" name="usuario_id" value="{{ $authUser->id }}">
        @endif

        {{-- Datos --}}
        <section class="space-y-3">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">receipt</span>
            Datos
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Fecha <span class="text-red-600">*</span></label>
              <input type="date" name="fecha_venta" value="{{ old('fecha_venta', now()->toDateString()) }}"
                     class="w-full h-10 rounded-lg border-gray-300 text-sm" required>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Cliente</label>
              <select name="cliente_id" class="w-full h-10 rounded-lg border-gray-300 text-sm">
                <option value="">Venta directa</option>
                @foreach ($clientes as $c)
                  <option value="{{ $c->id }}" @selected(old('cliente_id')==$c->id)>{{ $c->nombre ?? $c->razon_social }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Estatus <span class="text-red-600">*</span></label>
              <select name="estatus" class="w-full h-10 rounded-lg border-gray-300 text-sm" x-model="estatus" required>
                <option value="borrador">Borrador</option>
                <option value="prefactura" selected>Prefactura</option>
                <option value="facturada">Confirmar (Facturada)</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3" class="w-full rounded-lg border-gray-300 text-sm">{{ old('observaciones') }}</textarea>
          </div>
        </section>

        {{-- Productos --}}
        <section class="space-y-3">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">inventory_2</span>
            Productos
          </h2>

          @if (($isSA && !$empresaId) || (!$isSA && !$authUser->id_empresa))
            <div class="rounded-lg bg-amber-50 text-amber-800 p-3 text-sm">
              Selecciona primero la empresa para cargar el catálogo.
            </div>
          @else
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
          @endif
        </section>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('ventas.index', ['empresa_id' => $empresaId]) }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span>
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
            <span class="material-symbols-outlined mi text-base">save</span>
            Guardar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Alpine helpers --}}
  <script>
    function ventaItems() {
      return {
        estatus: 'prefactura',
        rows: [{ producto_id: '', cantidad: 1, precio_unitario: 0, descuento: 0 }],
        addRow() { this.rows.push({ producto_id: '', cantidad: 1, precio_unitario: 0, descuento: 0 }); },
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
