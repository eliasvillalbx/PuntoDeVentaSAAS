<div class="overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 text-gray-700">
      <tr>
        <th class="px-3 py-2 text-left w-72">Producto</th>
        <th class="px-3 py-2 text-right">Cantidad</th>
        <th class="px-3 py-2 text-right">Costo</th>
        <th class="px-3 py-2 text-right">Descuento</th>
        <th class="px-3 py-2 text-right">Total</th>
        <th class="px-3 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      <template x-for="(it, idx) in items" :key="idx">
        <tr class="hover:bg-gray-50">
          <td class="px-3 py-2">
            <select class="w-72 rounded-lg border-gray-300" x-model.number="it.id_producto" @change="syncNombre(idx)">
              <option value="">—</option>
              <template x-for="p in productos" :key="p.id">
                <option :value="p.id" x-text="p.nombre"></option>
              </template>
            </select>
            <input type="hidden" :name="`items[${idx}][id_producto]`" :value="it.id_producto">
          </td>
          <td class="px-3 py-2">
            <input type="number" step="0.01" min="0.01" x-model.number="it.cantidad" :name="`items[${idx}][cantidad]`"
                   class="w-28 text-right rounded-lg border-gray-300">
          </td>
          <td class="px-3 py-2">
            <input type="number" step="0.01" min="0" x-model.number="it.costo_unitario" :name="`items[${idx}][costo_unitario]`"
                   class="w-28 text-right rounded-lg border-gray-300">
          </td>
          <td class="px-3 py-2">
            <input type="number" step="0.01" min="0" x-model.number="it.descuento" :name="`items[${idx}][descuento]`"
                   class="w-28 text-right rounded-lg border-gray-300">
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
