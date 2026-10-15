<section class="rounded-card border border-line bg-surface p-3 shadow-sm md:p-6" aria-labelledby="current-month-display">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-ink" id="current-month-display" aria-live="polite">Cargando...</h2>
        <div class="flex gap-2">
            <x-ui.button variant="icon" id="prev-month" label="Mes anterior">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </x-ui.button>
            <x-ui.button variant="icon" id="next-month" label="Mes siguiente">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </x-ui.button>
        </div>
    </div>

    <div class="mb-2 grid grid-cols-7 gap-1 md:gap-2" aria-hidden="true">
        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $day)
            <div class="py-1 text-center text-min font-bold uppercase text-muted">{{ $day }}</div>
        @endforeach
    </div>

    {{-- Los días los pinta resources/js/pages/agenda/index.js, cada uno como un botón. --}}
    <div id="calendar-grid" class="grid grid-cols-7 gap-1 md:gap-2"></div>
</section>
