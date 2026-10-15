{{--
    Región de estado (docs/design/system.md → Región de estado): siempre montada y vacía, para
    que un lector de pantalla anuncie lo que resources/js/ui/status.js escriba después.
    `role="status"` recibe los avisos y `role="alert"` los errores de guardado.
    Una por marco (`placement="frame"`) y una dentro de cada diálogo (`placement="dialog"`).
--}}
@props([
    'placement' => 'frame',
])

@php
    $message = 'data-[shown]:rounded-box data-[shown]:border data-[shown]:px-4 data-[shown]:py-3 text-sm font-semibold';
    $wrapper = $placement === 'frame'
        ? 'pointer-events-none fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-md flex-col gap-2'
        : 'flex flex-col gap-2 px-4 md:px-6 has-data-[shown]:pb-3';
@endphp

<div data-ui-status-region {{ $attributes->merge(['class' => $wrapper]) }}>
    <div role="status" data-ui-status class="{{ $message }} data-[shown]:border-ink data-[shown]:bg-ink data-[shown]:text-on-dark"></div>
    <div role="alert" data-ui-alert class="{{ $message }} data-[shown]:border-danger data-[shown]:bg-danger-soft data-[shown]:text-danger"></div>
</div>
