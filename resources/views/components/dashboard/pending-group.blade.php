{{--
    Pendientes agrupados por día (spec 020, design.md → Componentes): el rótulo del día en 12 px
    mayúsculas `muted` ("Ayer · miércoles 21") como `h3`, y la lista de sus citas, con hora,
    paciente, tratamiento y doctor; las acciones van a la derecha desde 768 px. Sin insignia de
    estado. dashboard.js clona un grupo por día y una fila por cita.
--}}
@props([
    'label' => '',
])

<div {{ $attributes->merge(['class' => 'space-y-2']) }} data-pending-group>
    <h3 class="text-min font-bold uppercase tracking-wide text-muted" data-pending-day>{{ $label }}</h3>
    <ul class="divide-y divide-line rounded-box border border-line bg-surface" data-pending-rows>
        {{ $slot }}
    </ul>
</div>
