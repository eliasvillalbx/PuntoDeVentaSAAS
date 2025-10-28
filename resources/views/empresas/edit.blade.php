{{-- resources/views/empresas/edit.blade.php --}}
<x-app-layout>
  {{-- Header --}}
  <x-slot name="header">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 flex items-center gap-2">
        <span class="material-symbols-outlined mi">edit</span>
        Editar empresa
      </h1>
      <div class="flex items-center gap-2">
        <a href="{{ route('empresas.show', $empresa) }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">visibility</span>
          Ver
        </a>
        <a href="{{ route('empresas.index') }}"
           class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
          <span class="material-symbols-outlined mi text-base">arrow_back</span>
          Volver
        </a>
      </div>
    </div>
  </x-slot>

  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      {{-- Errores generales --}}
      @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 text-red-800 ring-1 ring-red-200 p-3">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ route('empresas.update', $empresa) }}" class="space-y-8" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- Identidad --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">badge</span>
            Identidad
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Razón social <span class="text-red-600">*</span></label>
              <input type="text" name="razon_social" value="{{ old('razon_social', $empresa->razon_social) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('razon_social') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Nombre comercial</label>
              <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial', $empresa->nombre_comercial) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('nombre_comercial') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- RFC + estado de validación --}}
            <div>
              <div class="flex items-center justify-between">
                <label class="block text-sm text-gray-700 mb-1">RFC <span class="text-red-600">*</span></label>
                <span id="rfcStatusBadge" class="hidden text-[11px] px-2 py-0.5 rounded-full font-medium"></span>
              </div>
              <input type="text" name="rfc" id="rfcInput" maxlength="13"
                     value="{{ old('rfc', $empresa->rfc) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm uppercase tracking-wide"
                     autocomplete="off">
              <p id="rfcHelp" class="text-xs text-gray-500 mt-1">Detectaremos automáticamente si es persona moral o física.</p>
              @error('rfc') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
              <p id="rfcError" class="hidden text-xs text-red-600 mt-1"></p>
            </div>

            {{-- Tipo de persona --}}
            <div>
              <label class="block text-sm text-gray-700 mb-1">Tipo de persona <span class="text-red-600">*</span></label>
              <select name="tipo_persona" id="tipoPersonaSelect"
                      class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                @php $tp = old('tipo_persona', $empresa->tipo_persona); @endphp
                <option value="moral"  @selected($tp === 'moral')>Moral</option>
                <option value="fisica" @selected($tp === 'fisica')>Física</option>
              </select>
              @error('tipo_persona') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-1">Régimen fiscal (código)</label>
              <input type="text" name="regimen_fiscal_code" value="{{ old('regimen_fiscal_code', $empresa->regimen_fiscal_code) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                     placeholder="601, 603, 605, 612, etc.">
              @error('regimen_fiscal_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-1">Sitio web</label>
              <input type="text" name="sitio_web" value="{{ old('sitio_web', $empresa->sitio_web) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                     placeholder="https://">
              @error('sitio_web') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
          </div>
        </section>

        {{-- Contacto --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">contact_mail</span>
            Contacto
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Email</label>
              <input type="email" name="email" value="{{ old('email', $empresa->email) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Teléfono</label>
              <input type="text" name="telefono" value="{{ old('telefono', $empresa->telefono) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Zona horaria --}}
            <div>
              <label class="block text-sm text-gray-700 mb-1">
                Zona horaria <span class="text-red-600">*</span>
              </label>
              @php $selectedTz = old('timezone', $empresa->timezone); @endphp
              <select name="timezone"
                      class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                      required>
                @foreach (($timezones ?? []) as $tz)
                  <option value="{{ $tz }}" @selected($selectedTz === $tz)>{{ $tz }}</option>
                @endforeach
              </select>
              @error('timezone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
          </div>
        </section>

        {{-- Dirección --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">home_pin</span>
            Dirección
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-1">Calle</label>
              <input type="text" name="calle" value="{{ old('calle', $empresa->calle) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('calle') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Número exterior</label>
              <input type="text" name="numero_exterior" value="{{ old('numero_exterior', $empresa->numero_exterior) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('numero_exterior') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Número interior</label>
              <input type="text" name="numero_interior" value="{{ old('numero_interior', $empresa->numero_interior) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('numero_interior') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-1">Colonia</label>
              <input type="text" name="colonia" value="{{ old('colonia', $empresa->colonia) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('colonia') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Municipio</label>
              <input type="text" name="municipio" value="{{ old('municipio', $empresa->municipio) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('municipio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-1">Ciudad</label>
              <input type="text" name="ciudad" value="{{ old('ciudad', $empresa->ciudad) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('ciudad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-1">Estado</label>
              <input type="text" name="estado" value="{{ old('estado', $empresa->estado) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('estado') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- País obligatorio --}}
            <div>
              <label class="block text-sm text-gray-700 mb-1">País <span class="text-red-600">*</span></label>
              <input type="text" name="pais" value="{{ old('pais', $empresa->pais) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm" required>
              @error('pais') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm text-gray-700 mb-1">Código postal</label>
              <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $empresa->codigo_postal) }}"
                     class="w-full h-10 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
              @error('codigo_postal') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
          </div>
        </section>

        {{-- Configuración / Estado / Logo --}}
        <section class="space-y-4">
          <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
            <span class="material-symbols-outlined mi text-base">settings</span>
            Configuración
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="flex items-center gap-2">
              <input type="checkbox" name="activa" value="1" id="activa"
                     class="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                     @checked(old('activa', $empresa->activa))>
              <label for="activa" class="text-sm text-gray-700">Empresa activa</label>
            </div>

            {{-- Logo actual + subir nuevo --}}
            <div class="sm:col-span-2">
              <label class="block text-sm text-gray-700 mb-1">Logo (imagen)</label>

              <div class="flex items-center gap-4">
                @php
                  $path = $empresa->logo_path;
                  $isDirectUrl = $path && \Illuminate\Support\Str::startsWith($path, ['http://','https://','/storage/']);
                  $existsOnDisk = $path && !$isDirectUrl && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
                  $logoUrl = $isDirectUrl ? $path : ($existsOnDisk ? \Illuminate\Support\Facades\Storage::url($path) : null);
                @endphp

                @if ($logoUrl)
                  <img id="currentLogo" src="{{ $logoUrl }}" alt="Logo actual" class="h-14 w-14 rounded-lg object-cover ring-1 ring-gray-200">
                @else
                  <div id="currentLogoFallback" class="h-14 w-14 rounded-lg bg-gray-100 ring-1 ring-gray-200 grid place-items-center">
                    <span class="material-symbols-outlined text-gray-500">image</span>
                  </div>
                @endif

                <input type="file" name="logo" id="logoInput" accept="image/*"
                  class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <button type="button" id="clearLogoBtn" class="hidden h-10 px-3 rounded-lg border text-sm">Quitar</button>
              </div>

              @error('logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

              <div id="logoPreviewWrap" class="mt-3 hidden">
                <img id="logoPreview" src="#" alt="Vista previa del nuevo logo" class="h-16 rounded-lg border">
              </div>

              <p class="text-xs text-gray-500 mt-1">
                Subir un archivo reemplazará el logo actual. Se guarda en <code>storage/app/public/empresas</code>.
              </p>
            </div>
          </div>
        </section>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-2">
          <a href="{{ route('empresas.show', $empresa) }}"
             class="inline-flex items-center gap-2 h-10 px-4 rounded-xl border border-gray-300 text-gray-700 text-sm hover:bg-gray-50">
            <span class="material-symbols-outlined mi text-base">close</span>
            Cancelar
          </a>
          <button type="submit"
                  class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-indigo-600 text-white text-sm font-medium shadow hover:bg-indigo-700">
            <span class="material-symbols-outlined mi text-base">save</span>
            Guardar cambios
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ====== JS: Validación/autocompletado RFC + preview de logo ====== --}}
  <script>
    (function () {
      // ===== RFC =====
      const rfcInput = document.getElementById('rfcInput');
      const tipoSelect = document.getElementById('tipoPersonaSelect');
      const rfcError = document.getElementById('rfcError');
      const rfcBadge = document.getElementById('rfcStatusBadge');

      const MORAL_RE   = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/i;
      const FISICA_RE  = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/i;
      const GENERIC_RE = /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/i;

      function toUpperTrim(v){ return (v||'').toString().toUpperCase().trim(); }
      function validRFCDate(yy,mm,dd){
        const y = parseInt(yy,10), m = parseInt(mm,10), d = parseInt(dd,10);
        if (!(m>=1 && m<=12 && d>=1 && d<=31)) return false;
        const fullYear = y < 30 ? 2000 + y : 1900 + y;
        const date = new Date(fullYear, m-1, d);
        return date.getFullYear()===fullYear && (date.getMonth()+1)===m && date.getDate()===d;
      }
      function annotateBadge(ok, text){
        rfcBadge.classList.remove('hidden');
        rfcBadge.textContent = text;
        rfcBadge.className = '';
        rfcBadge.classList.add('text-[11px]','px-2','py-0.5','rounded-full','font-medium');
        if (ok===true) rfcBadge.classList.add('bg-green-100','text-green-700');
        else if (ok===false) rfcBadge.classList.add('bg-red-100','text-red-700');
        else rfcBadge.classList.add('bg-amber-100','text-amber-700');
      }
      function setFieldValidity(ok, msg=''){
        if (ok){
          rfcInput.classList.remove('border-red-400','focus:border-red-500','focus:ring-red-500');
          rfcInput.classList.add('border-green-400');
          rfcError?.classList.add('hidden'); if (rfcError) rfcError.textContent = '';
        } else {
          rfcInput.classList.remove('border-green-400');
          rfcInput.classList.add('border-red-400','focus:border-red-500','focus:ring-red-500');
          if (rfcError){ rfcError.classList.remove('hidden'); rfcError.textContent = msg; }
        }
      }
      function detectAndValidateRFC(){
        let v = toUpperTrim(rfcInput.value);
        rfcInput.value = v;
        if (!v){ annotateBadge(null,'—'); setFieldValidity(true); return; }
        if (v.length<12 || v.length>13 || !GENERIC_RE.test(v)){
          annotateBadge(false,'RFC no válido'); setFieldValidity(false,'El RFC debe tener 12 (moral) o 13 (física) caracteres con estructura válida.'); return;
        }
        const base = v.length===12 ? 3 : 4;
        const yy=v.substr(base,2), mm=v.substr(base+2,2), dd=v.substr(base+4,2);
        const isDateOk = validRFCDate(yy,mm,dd);
        const detectedType = v.length===12 ? 'moral' : 'fisica';
        if (detectedType==='moral' && !MORAL_RE.test(v)){
          annotateBadge(false,'RFC moral inválido'); setFieldValidity(false,'Estructura de RFC para persona moral inválida.'); return;
        }
        if (detectedType==='fisica' && !FISICA_RE.test(v)){
          annotateBadge(false,'RFC física inválido'); setFieldValidity(false,'Estructura de RFC para persona física inválida.'); return;
        }
        if (tipoSelect && tipoSelect.value!==detectedType) tipoSelect.value = detectedType;
        if (!isDateOk){
          annotateBadge(false,'Fecha inválida'); setFieldValidity(false,'La fecha YYMMDD dentro del RFC no es válida.'); return;
        }
        annotateBadge(true, detectedType==='moral' ? 'Válido · Moral' : 'Válido · Física');
        setFieldValidity(true);
      }
      rfcInput?.addEventListener('input', detectAndValidateRFC);
      rfcInput?.addEventListener('blur', detectAndValidateRFC);
      // Validar el valor inicial
      if (rfcInput?.value) detectAndValidateRFC();

      // ===== Logo preview =====
      const logoInput = document.getElementById('logoInput');
      const clearBtn = document.getElementById('clearLogoBtn');
      const previewWrap = document.getElementById('logoPreviewWrap');
      const previewImg = document.getElementById('logoPreview');

      function clearLogo(){
        if (!logoInput) return;
        logoInput.value='';
        if (previewImg) previewImg.src='#';
        previewWrap?.classList.add('hidden');
        clearBtn?.classList.add('hidden');
      }
      function onLogoChange(){
        const file = logoInput?.files?.[0];
        if (!file){ clearLogo(); return; }
        const url = URL.createObjectURL(file);
        if (previewImg) previewImg.src = url;
        previewWrap?.classList.remove('hidden');
        clearBtn?.classList.remove('hidden');
      }
      logoInput?.addEventListener('change', onLogoChange);
      clearBtn?.addEventListener('click', clearLogo);
    })();
  </script>
</x-app-layout>
