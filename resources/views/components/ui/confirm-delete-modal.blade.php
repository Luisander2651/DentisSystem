@props([
    'modalId' => 'confirm-delete-modal',
    'title' => '¿Estás seguro?',
    'messagePrefix' => '¿Está seguro de que desea eliminar a ',
    'messageSuffix' => '?',
    'confirmText' => 'Sí, eliminar',
    'cancelText' => 'Cancelar',
])

<x-ui.dialog :id="$modalId" :title="$title" size="sm" data-confirm-delete-modal>
    <p class="text-sm leading-6 text-ink">
        {{ $messagePrefix }} <span class="font-semibold wrap-anywhere" data-confirm-delete-name></span>{{ $messageSuffix }}
    </p>
    <p class="mt-2 text-sm font-semibold text-danger">Esta acción no se puede deshacer.</p>

    <x-slot:footer>
        <x-ui.button data-confirm-delete-cancel>{{ $cancelText }}</x-ui.button>
        <x-ui.button variant="danger" data-confirm-delete-submit>{{ $confirmText }}</x-ui.button>
    </x-slot:footer>
</x-ui.dialog>
