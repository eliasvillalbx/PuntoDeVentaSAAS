<x-app-layout>
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">add_card</span>
        Nueva suscripción
      </h1>
      <a href="{{ route('suscripciones.index') }}"
         class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border text-sm text-gray-700 hover:bg-gray-50">
        <span class="material-symbols-outlined mi text-base">arrow_back</span>
        Volver
      </a>
    </div>
  </x-slot>

  <div class="max-w-3xl mx-auto">
    {{-- Mensajes en contenido (no navbar) --}}
    @if (session('success'))
      <div class="mb-4 rounded-lg bg-green-50 text-green-800 ring-1 ring-green-200 p-3">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
      <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <form method="POST" action="{{ route('suscripciones.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Empresa <span class="text-red-600">*</span></label>
            <select name="empresa_id" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              <option value="">Selecciona…</option>
              @foreach ($empresas as $e)
                <option value="{{ $e->id }}" @selected(old('empresa_id') == $e->id)>{{ $e->razon_social }}</option>
              @endforeach
            </select>
            @error('empresa_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Plan <span class="text-red-600">*</span></label>
            @php $planOld = old('plan','1_mes'); @endphp
            <select name="plan" id="planSelect" class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm" required>
              <option value="1_mes"   @selected($planOld==='1_mes')>1 mes</option>
              <option value="6_meses" @selected($planOld==='6_meses')>6 meses</option>
              <option value="1_año"   @selected($planOld==='1_año')>1 año</option>
              <option value="3_años"  @selected($planOld==='3_años')>3 años</option>
            </select>
            @error('plan') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Fecha de inicio</label>
            @php $hoy = old('fecha_inicio', now()->format('Y-m-d')); @endphp
            <input type="date" name="fecha_inicio" id="fechaInicio" value="{{ $hoy }}"
                   class="w-full h-10 rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
            @error('fecha_inicio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-1">Fecha de vencimiento (auto)</label>
            <input type="text" id="fechaVencimiento" class="w-full h-10 rounded-lg border-gray-300 bg-gray-50 text-sm" readonly>
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <a href="{{ route('suscripciones.index') }}" class="h-10 px-4 rounded-lg border text-sm text-gray-700">Cancelar</a>
          <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function(){
    const planSel = document.getElementById('planSelect');
    const finput  = document.getElementById('fechaInicio');
    const fout    = document.getElementById('fechaVencimiento');

    function addMonthsNoOverflow(date, months){
      const d = new Date(date.getTime());
      const day = d.getDate();
      d.setMonth(d.getMonth() + months);
      if (d.getDate() < day) d.setDate(0);
      return d;
    }
    function fmt(d){
      const m = (d.getMonth()+1).toString().padStart(2,'0');
      const day = d.getDate().toString().padStart(2,'0');
      return `${d.getFullYear()}-${m}-${day}`;
    }
    function planToMonths(plan){
      switch (plan) {
        case '1_mes':   return 1;
        case '6_meses': return 6;
        case '1_año':   return 12;
        case '3_años':  return 36;
        default:        return 1;
      }
    }
    function recalc(){
      const plan  = planSel.value;
      const start = finput.value ? new Date(finput.value + 'T00:00:00') : new Date();
      const end   = addMonthsNoOverflow(start, planToMonths(plan));
      fout.value  = fmt(end);
    }

    planSel?.addEventListener('change', recalc);
    finput?.addEventListener('change', recalc);
    recalc();
  })();
  </script>
</x-app-layout>
