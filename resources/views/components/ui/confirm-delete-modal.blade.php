@props([
    'modalId' => 'confirm-delete-modal',
    'title' => 'Confirmar eliminacion',
    'messagePrefix' => 'Esta segura que desea eliminar',
    'confirmText' => 'Eliminar',
    'cancelText' => 'Cancelar',
])

<div
    id="{{ $modalId }}"
    data-confirm-modal
    class="fixed inset-0 z-100 hidden items-center justify-center bg-slate-900/50 p-4"
>
    <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
        </div>

        <div class="px-5 py-4">
            <p class="text-sm text-slate-600">
                {{ $messagePrefix }}
                <span data-confirm-target class="font-semibold text-slate-900"></span>
                ?
            </p>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
            <button
                type="button"
                data-confirm-cancel
                class="cursor-pointer rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
            >
                {{ $cancelText }}
            </button>
            <button
                type="button"
                data-confirm-accept
                class="cursor-pointer rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700"
            >
                {{ $confirmText }}
            </button>
        </div>
    </div>
</div>
