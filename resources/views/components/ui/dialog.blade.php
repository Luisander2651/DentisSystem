{{--
    Diálogo base (docs/design/system.md → Diálogo), sobre el elemento nativo <dialog>: el título
    es su nombre accesible, el foco queda dentro y vuelve al control que lo abrió, Escape cierra,
    el cuerpo se desplaza y el pie de acciones queda fijo. Lleva su propia región de estado.
    Lo abre y lo cierra resources/js/ui/dialog.js. Un formulario va en el cuerpo y sus botones,
    en el slot `footer` con el atributo `form`. `tone="dark"` lo pinta sobre tinta (el visor de
    imágenes) y marca la superficie como oscura para el foco.
--}}
@props([
    'id',
    'title',
    'size' => 'md',
    'tone' => 'light',
])

@php
    $widths = ['sm' => 'max-w-md', 'md' => 'max-w-xl', 'lg' => 'max-w-3xl'];
    $dark = $tone === 'dark';
    $surface = $dark ? 'border-ink bg-ink text-on-dark' : 'border-line bg-surface text-ink';
    $rule = $dark ? 'border-on-dark/20' : 'border-line';
@endphp

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
    data-ui-dialog
    @if ($dark) data-surface="dark" @endif
    {{ $attributes->merge(['class' => 'm-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] overflow-hidden rounded-card border p-0 shadow-lg backdrop:bg-overlay open:flex open:flex-col '.$surface.' '.($widths[$size] ?? $widths['md'])]) }}
>
    <header class="flex items-center justify-between gap-3 border-b {{ $rule }} px-4 py-3 md:px-6">
        <h2 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h2>
        <x-ui.button variant="icon" label="Cerrar" data-dialog-close>
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg>
        </x-ui.button>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 md:px-6" data-dialog-body>
        {{ $slot }}
    </div>

    <x-ui.status placement="dialog" />

    @isset($footer)
        <footer class="flex flex-wrap justify-end gap-2 border-t {{ $rule }} px-4 py-3 md:px-6">
            {{ $footer }}
        </footer>
    @endisset
</dialog>
