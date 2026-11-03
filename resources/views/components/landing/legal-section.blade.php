{{-- Sección de un documento legal (x-landing.legal-document): el id es el destino de su entrada en el índice. --}}
@props([
    'id',
    'title',
])

<section id="{{ $id }}" {{ $attributes->merge(['class' => 'scroll-mt-24 space-y-3 leading-relaxed text-muted']) }}>
    <h2 class="text-xl font-bold text-ink">{{ $title }}</h2>
    {{ $slot }}
</section>
