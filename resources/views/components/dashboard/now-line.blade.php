{{--
    Línea de ahora de una lista del día (spec 020, design.md → Componentes): regla de 2 px en
    `primary` con "Ahora · HH:MM" en 12 px / 700 `muted`. Decorativa: separa lo pasado de lo que
    viene y los lectores de pantalla no la leen. Va como un `li` dentro de la `ol` de la agenda.
--}}
@props([
    'time' => '',
])

<li {{ $attributes->merge(['class' => 'flex items-center gap-3 py-1']) }} aria-hidden="true" data-now-line>
    <span class="text-min font-bold text-muted">Ahora · <span data-now-time>{{ $time }}</span></span>
    <span class="h-0.5 flex-1 rounded-full bg-primary"></span>
</li>
