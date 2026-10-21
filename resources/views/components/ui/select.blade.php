{{--
    Selector del sistema de diseño (docs/design/system.md → Selector · spec 019): las medidas de
    x-ui.input (44 px de alto, letra de 16 px en tinta, borde field, radio control), etiqueta
    visible, texto de ayuda y error enlazados con aria-describedby. `options` es valor => etiqueta
    y `value`, el valor elegido.
--}}
@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'options' => [],
    'value' => null,
    'hint' => null,
    'errorText' => null,
])

@php
    $selectId = $id ?? $name ?? ('ui-select-' . uniqid());
    $chosen = (string) ($name ? old($name, $value) : $value);
    $describedBy = trim(($hint ? $selectId . '-hint ' : '') . ($errorText ? $selectId . '-error' : ''));
    $border = $errorText ? 'border-2 border-danger' : 'border border-field';
@endphp

<div class="w-full">
    @if ($label)
        <label for="{{ $selectId }}" class="mb-1.5 block text-sm font-semibold text-ink">{{ $label }}</label>
    @endif

    <select
        id="{{ $selectId }}"
        @if ($name) name="{{ $name }}" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($errorText) aria-invalid="true" @endif
        {{ $attributes->merge(['class' => 'block h-control w-full rounded-control bg-surface px-3 text-control text-ink ' . $border]) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $optionValue === $chosen)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint)
        <p id="{{ $selectId }}-hint" class="mt-1 text-min text-muted">{{ $hint }}</p>
    @endif

    @if ($errorText)
        <p id="{{ $selectId }}-error" class="mt-1 text-sm font-semibold text-danger">{{ $errorText }}</p>
    @endif
</div>
