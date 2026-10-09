{{--
    Registro de etiqueta y valor (docs/design/system.md → Registro), para dentro de un <dl>.
    Sustituye a las tablas del expediente por debajo de `md`.
--}}
@props([
    'label',
])

<div {{ $attributes->merge(['class' => 'border-b border-line py-2']) }}>
    <dt class="text-min font-bold uppercase tracking-wide text-muted">{{ $label }}</dt>
    <dd class="text-sm text-ink wrap-anywhere">{{ $slot }}</dd>
</div>
