{{-- resources/views/chat/widget.blade.php --}}
<div
    x-data="{
        open: false,
    }"
    x-cloak
    class="fixed bottom-4 right-4 z-[70] flex flex-col items-end space-y-2 space-y-reverse"
>
    {{-- Panel desplegable --}}
    <div
        x-show="open"
        x-transition
        class="mb-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden"
    >
        <div class="px-3 py-2 bg-sky-600 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined mi text-base">chat</span>
                <span class="text-sm font-semibold">Chat empresarial</span>
            </div>
            <button
                type="button"
                class="text-white/80 hover:text-white"
                @click="open = false"
            >
                <span class="material-symbols-outlined mi text-sm">close</span>
            </button>
        </div>

        <div class="p-3 text-sm text-gray-700 space-y-2">
            <p>
                Chatea en tiempo real con usuarios de tu empresa.
            </p>

            <p class="text-xs text-gray-500">
                Haz clic en el botón de abajo para abrir el módulo completo de chat tipo Messenger.
            </p>

            <div class="pt-2 flex justify-end">
                <a
                    href="{{ route('chat.index') }}"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md bg-sky-600 text-white text-xs font-medium hover:bg-sky-700"
                >
                    <span class="material-symbols-outlined mi text-sm">open_in_full</span>
                    <span>Abrir chat</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Botón flotante --}}
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center justify-center h-12 w-12 rounded-full shadow-lg
               bg-sky-600 text-white hover:bg-sky-700 focus:outline-none focus:ring-2
               focus:ring-offset-2 focus:ring-sky-500"
        aria-label="Abrir chat empresarial"
    >
        <span class="material-symbols-outlined mi">
            chat
        </span>
    </button>
</div>
