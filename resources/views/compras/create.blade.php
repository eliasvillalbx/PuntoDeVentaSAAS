<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">add_shopping_cart</span>
        Nueva compra
      </h1>
      <a href="{{ route('compras.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto space-y-6"
       x-data="formCompra()"
       x-init='init(@json($productos), @json($proveedores), @json($prefill))'>

    {{-- Errores --}}
    @if ($errors->any())
      <div class="rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">
        {{ $errors->first() }}
      </div>
    @endif

    {{-- Aviso si no hay catálogos --}}
    @if (($proveedores ?? collect())->isEmpty() || ($productos ?? collect())->isEmpty())
      <div class="rounded-lg bg-amber-50 text-amber-800 ring-1 ring-amber-200 p-3 space-y-1">
        @if (($proveedores ?? collect())->isEmpty())
          <div>⚠️ No hay <strong>proveedores</strong>. <a href="{{ route('proveedores.create') }}" class="underline">Crear proveedor</a></div>
        @endif
        @if (($productos ?? collect())->isEmpty())
          <div>⚠️ No hay <strong>productos</strong>. <a href="{{ route('productos.create') }}" class="underline">Crear producto</a></div>
        @endif
      </div>
    @endif

    <form method="POST" action="{{ route('compras.store') }}" class="bg-white rounded-xl border p-6 space-y-6">
      @csrf

      <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="sm:col-span-2">
          <label class="text-sm text-gray-600">Proveedor</label>
          <select name="id_proveedor" x-model="proveedor_id" class="w-full rounded-lg border-gray-300" required @change="onProveedorChange()">
            <option value="">Selecciona…</option>
            <template x-for="p in proveedores" :key="p.id">
              <option :value="p.id" x-text="p.nombre"></option>
            </template>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Fecha</label>
          <input type="date" name="fecha_compra" class="w-full rounded-lg border-gray-300" value="{{ now()->toDateString() }}" required>
        </div>
        <div>
          <label class="text-sm text-gray-600">Estatus</label>
          <select name="estatus" class="w-full rounded-lg border-gray-300" required>
            <option value="borrador">Borrador</option>
            <option value="orden_compra">Orden de compra</option>
            <option value="recibida">Recibida</option>
            <option value="cancelada">Cancelada</option>
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-gray-600">Observaciones</label>
        <textarea name="observaciones" rows="2" class="w-full rounded-lg border-gray-300"></textarea>
      </div>

      {{-- Tabla de items --}}
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 text-gray-700">
            <tr>
              <th class="px-3 py-2 text-left w-80">Producto</th>
              <th class="px-3 py-2 text-right">Cantidad</th>
              <th class="px-3 py-2 text-right">Costo prov.</th>
              <th class="px-3 py-2 text-right">Descuento</th>
              <th class="px-3 py-2 text-right">Total</th>
              <th class="px-3 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template x-for="(it, idx) in items" :key="idx">
              <tr class="hover:bg-gray-50 align-top">
                <td class="px-3 py-2">
                  <div class="flex items-center gap-2">
                    <select class="w-72 rounded-lg border-gray-300" x-model.number="it.id_producto" @change="onProductoChange(idx)">
                      <option value="">—</option>
                      <template x-for="p in productos" :key="p.id">
                        <option :value="p.id" x-text="p.nombre"></option>
                      </template>
                    </select>

                    {{-- Comparador: también permite elegir proveedor y autollenar costo --}}
                    <button type="button"
                            class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700"
                            @click="openComparador(idx)"
                            title="Comparar precios por distribuidor">
                      <span class="material-symbols-outlined mi text-base">stacked_bar_chart</span>
                      Comparar
                    </button>
                  </div>

                  {{-- Mensaje si falta costo --}}
                  <template x-if="it.costo_msg">
                    <div class="mt-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1" x-text="it.costo_msg"></div>
                  </template>

                  <input type="hidden" :name="`items[${idx}][id_producto]`" :value="it.id_producto">
                </td>

                <td class="px-3 py-2">
                  <input type="number" step="0.01" min="0.01" x-model.number="it.cantidad" :name="`items[${idx}][cantidad]`"
                         class="w-28 text-right rounded-lg border-gray-300" @input="recalc()">
                </td>

                <td class="px-3 py-2 text-right">
                  {{-- Costo automático (solo lectura) --}}
                  <div class="font-medium" x-text="fmtNum(it.costo_unitario || 0)"></div>
                  <input type="hidden" :name="`items[${idx}][costo_unitario]`" :value="it.costo_unitario || 0">
                </td>

                <td class="px-3 py-2">
                  <input type="number" step="0.01" min="0" x-model.number="it.descuento" :name="`items[${idx}][descuento]`"
                         class="w-28 text-right rounded-lg border-gray-300" @input="recalc()">
                </td>

                <td class="px-3 py-2 text-right" x-text="fmt(totalLinea(it))"></td>

                <td class="px-3 py-2 text-right">
                  <button type="button" @click="remove(idx)" class="text-red-700 hover:underline">Quitar</button>
                </td>
              </tr>
            </template>

            <tr>
              <td colspan="6" class="px-3 py-2">
                <button type="button" @click="add()" class="inline-flex items-center gap-1 text-cyan-700 hover:underline">
                  <span class="material-symbols-outlined mi text-base">add</span> Agregar producto
                </button>
              </td>
            </tr>
          </tbody>

          <tfoot class="bg-gray-50">
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-medium">Subtotal</td>
              <td class="px-3 py-2 text-right" x-text="fmt(subtotal())"></td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-medium">IVA (16%)</td>
              <td class="px-3 py-2 text-right" x-text="fmt(iva())"></td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3"></td>
              <td class="px-3 py-2 text-right font-bold">Total</td>
              <td class="px-3 py-2 text-right font-bold" x-text="fmt(total())"></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="flex items-center justify-end gap-3">
        <button class="h-10 px-4 rounded-xl bg-gray-800 text-white hover:bg-gray-900">Guardar</button>
      </div>
    </form>

    {{-- MODAL COMPARADOR (dentro del mismo x-data) --}}
    <div x-show="comparador.open" x-transition class="fixed inset-0 z-50 flex items-center justify-center" x-cloak>
      <div class="absolute inset-0 bg-black/50" @click="comparador.open=false"></div>
      <div class="relative bg-white w-full max-w-4xl mx-4 rounded-2xl shadow-xl border p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">stacked_bar_chart</span>
            Comparar precios — <span x-text="comparador.producto"></span>
          </h3>
          <button class="text-gray-500 hover:text-gray-700" @click="comparador.open=false">
            <span class="material-symbols-outlined mi">close</span>
          </button>
        </div>

        <template x-if="comparador.loading">
          <div class="p-6 text-center text-gray-500">Cargando proveedores…</div>
        </template>

        <template x-if="!comparador.loading && !comparador.rows.length">
          <div class="p-6 text-center text-gray-500">
            No hay proveedores vinculados a este producto.
            <div class="mt-3">
              <a href="{{ route('proveedores.create') }}" class="text-cyan-700 hover:underline">Crear proveedor</a>
            </div>
          </div>
        </template>

        <template x-if="!comparador.loading && comparador.rows.length">
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-gray-700">
                <tr>
                  <th class="px-3 py-2 text-left">Proveedor</th>
                  <th class="px-3 py-2 text-left">SKU prov.</th>
                  <th class="px-3 py-2 text-right">Costo</th>
                  <th class="px-3 py-2 text-left">Moneda</th>
                  <th class="px-3 py-2 text-right">Lead time</th>
                  <th class="px-3 py-2 text-right">MOQ</th>
                  <th class="px-3 py-2 text-left">Banderas</th>
                  <th class="px-3 py-2 text-right">Acción</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <template x-for="prov in comparador.rows" :key="prov.id">
                  <tr :class="prov.costo === comparador.minCosto && prov.activo ? 'bg-green-50/60' : ''">
                    <td class="px-3 py-2 font-medium text-gray-900" x-text="prov.nombre"></td>
                    <td class="px-3 py-2 text-gray-700" x-text="prov.sku_proveedor || '—'"></td>
                    <td class="px-3 py-2 text-right text-gray-900">
                      <span x-text="fmtNum(prov.costo)"></span>
                    </td>
                    <td class="px-3 py-2 text-gray-700" x-text="prov.moneda"></td>
                    <td class="px-3 py-2 text-right text-gray-700" x-text="prov.lead_time_dias + ' días'"></td>
                    <td class="px-3 py-2 text-right text-gray-700" x-text="prov.moq"></td>
                    <td class="px-3 py-2">
                      <div class="flex flex-wrap gap-1">
                        <template x-if="prov.preferido">
                          <span class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Preferido</span>
                        </template>
                        <template x-if="prov.activo">
                          <span class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-green-100 text-green-700">Activo</span>
                        </template>
                        <template x-if="prov.costo === comparador.minCosto && prov.activo">
                          <span class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Mejor precio</span>
                        </template>
                      </div>
                    </td>
                    <td class="px-3 py-2 text-right">
                      <button type="button"
                              class="inline-flex items-center gap-1 text-xs px-2.5 py-1.5 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700"
                              @click="usarProveedor(prov)">
                        <span class="material-symbols-outlined mi text-base">done</span>
                        Usar
                      </button>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </div>
  </div>

  <script>
    function formCompra() {
      return {
        productos: [], proveedores: [],
        items: [],
        proveedor_id: '',
        comparador: { open:false, loading:false, rows:[], idx:null, producto:'', minCosto:null },

        init(prod, prov, prefill) {
          this.productos = prod || [];
          this.proveedores = prov || [];
          this.items = (prefill && prefill.length) ? prefill : [{
            id_producto:'', nombre:'', cantidad:1, costo_unitario:null, descuento:0, costo_msg: ''
          }];
        },

        add(){ this.items.push({ id_producto:'', nombre:'', cantidad:1, costo_unitario:null, descuento:0, costo_msg:'' }); },
        remove(i){ this.items.splice(i,1); if(!this.items.length) this.add(); this.recalc(); },

        // Disparadores
        onProveedorChange(){ this.items.forEach((_,i)=> this.refreshCostoIdx(i)); },
        onProductoChange(i){ this.syncNombre(i); this.refreshCostoIdx(i); },

        syncNombre(i){
          const idp = this.items[i].id_producto;
          const p = this.productos.find(x=>x.id==idp);
          this.items[i].nombre = p ? p.nombre : '';
        },

        async refreshCostoIdx(i){
          const it = this.items[i];
          it.costo_msg = '';
          it.costo_unitario = 0;

          if (!this.proveedor_id || !it.id_producto) { this.recalc(); return; }

          try {
            const url = `{{ url('/productos') }}/${it.id_producto}/costo-para/${this.proveedor_id}`;
            const res = await fetch(url);
            if (!res.ok) {
              const j = await res.json().catch(()=>({error:'Error'}));
              it.costo_msg = j.error || 'Sin costo definido para este proveedor';
              it.costo_unitario = 0;
            } else {
              const j = await res.json();
              it.costo_unitario = +j.costo || 0;
            }
          } catch (e) {
            console.error(e);
            it.costo_msg = 'No se pudo obtener el costo';
            it.costo_unitario = 0;
          } finally {
            this.recalc();
          }
        },

        // Comparador (puede cambiar proveedor global)
        async openComparador(idx){
          const it = this.items[idx];
          if (!it.id_producto) { alert('Selecciona un producto primero.'); return; }

          const p = this.productos.find(x => x.id == it.id_producto);
          this.comparador = { open:true, loading:true, rows:[], idx:idx, producto: (p ? p.nombre : ''), minCosto:null };

          try {
            const res = await fetch(`{{ url('/productos') }}/${it.id_producto}/proveedores-json`);
            if (!res.ok) throw new Error('Error al cargar proveedores');
            const json = await res.json();
            const rows = Array.isArray(json.data) ? json.data : [];
            const activos = rows.filter(r => !!r.activo);
            const min = activos.length ? Math.min(...activos.map(r => +r.costo)) : (rows.length ? Math.min(...rows.map(r=>+r.costo)) : null);

            this.comparador.rows = rows;
            this.comparador.minCosto = isFinite(min) ? min : null;
          } catch (e) {
            console.error(e);
            this.comparador.rows = [];
            this.comparador.minCosto = null;
          } finally {
            this.comparador.loading = false;
          }
        },

        usarProveedor(prov){
          this.proveedor_id = String(prov.id);
          this.items.forEach((_,i)=> this.refreshCostoIdx(i));
          this.comparador.open = false;
        },

        // Cálculos
        totalLinea(it){ const t = (it.cantidad*(it.costo_unitario||0)) - (it.descuento||0); return t>0? t:0; },
        subtotal(){ return +(this.items.reduce((s,it)=> s + this.totalLinea(it), 0)).toFixed(2); },
        iva(){ return +(this.subtotal()*0.16).toFixed(2); },
        total(){ return +(this.subtotal()+this.iva()).toFixed(2); },
        recalc(){ /* fuerza render */ },

        // Formatos
        fmt(n){ return new Intl.NumberFormat('es-MX',{style:'currency', currency:'MXN'}).format(n||0); },
        fmtNum(n){ return new Intl.NumberFormat('es-MX',{minimumFractionDigits:2, maximumFractionDigits:2}).format(n||0); },
      }
    }
  </script>
</x-app-layout>
