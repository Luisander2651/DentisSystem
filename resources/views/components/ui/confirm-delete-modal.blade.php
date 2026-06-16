@props([
    'modalId' => 'confirm-delete-modal',
    'title' => '¿Estás seguro?',
    'messagePrefix' => '¿Está seguro de que desea eliminar a ',
    'messageSuffix' => '?',
    'confirmText' => 'Sí, eliminar',
    'cancelText' => 'Cancelar',
])

<div
    id="{{ $modalId }}"
    data-confirm-delete-modal
    class="fixed inset-0 z-110 hidden overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Background backdrop with blur --}}
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" aria-hidden="true" data-confirm-delete-cancel></div>

    {{-- Modal content container --}}
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative w-full max-w-md transform overflow-hidden rounded-[1.75rem] bg-white text-left shadow-xl transition-all sm:my-8">
                <div class="bg-white px-8 pt-8 pb-6 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-600 mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-xl font-extrabold text-slate-900">{{ $title }}</h3>
                    <p class="mt-3 text-sm font-medium text-slate-500 leading-relaxed">
                        {{ $messagePrefix }} <span class="font-bold text-slate-900" data-confirm-delete-name></span>{{ $messageSuffix }}
                        <br>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-red-500 mt-2 block">Esta acción no se puede deshacer</span>
                    </p>

                    <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button
                            type="button"
                            data-confirm-delete-cancel
                            class="w-full sm:w-auto cursor-pointer rounded-2xl border border-slate-200 bg-white px-8 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95"
                        >
                            {{ $cancelText }}
                        </button>
                        <button
                            type="button"
                            data-confirm-delete-submit
                            class="w-full sm:w-auto cursor-pointer rounded-2xl bg-red-600 px-8 py-3 text-sm font-bold text-white shadow-lg shadow-red-600/20 transition hover:bg-red-700 active:scale-95"
                        >
                            {{ $confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
