@extends('layouts.admin')

@section('title', 'Reportes - Dentissa')

{{--
    Spec 019 · Reportes (docs/specs/019-reportes-mensuales/design.md, composición C). La vista solo
    trae la lista de meses y el elegido (ShowReportsPageController); las cifras llegan por la API y
    resources/js/pages/reportes/index.js las pinta en estos contenedores y plantillas.
--}}
@section('content')
<div class="space-y-6" data-reports-root>
    <x-ui.page-hero
        title="Reportes"
        description="Indicadores de la clínica por mes, comparados con el mes anterior."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 3v18h18" /><path d="M7 15v3" /><path d="M12 10v8" /><path d="M17 6v12" />
            </svg>
        </x-slot:icon>

        <x-slot:actions>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-end">
                <div class="w-full sm:w-56">
                    <x-ui.select name="mes" id="reports-month" label="Mes" :options="$months" :value="$chosenMonth" data-reports-month />
                </div>
                <x-ui.button variant="primary" class="w-full sm:w-auto" data-reports-download>
                    <span class="hidden h-4 w-4 animate-spin rounded-full border-2 border-ink border-t-transparent" aria-hidden="true" data-reports-download-spinner></span>
                    Descargar Excel
                </x-ui.button>
            </div>
        </x-slot:actions>
    </x-ui.page-hero>

    <p hidden data-reports-current-notice class="rounded-box border border-secondary bg-primary-soft px-4 py-3 text-sm text-ink"></p>

    {{-- Error de carga de la pantalla (design.md · A33, A48): persiste hasta reintentar; no es región viva. --}}
    <div hidden data-reports-load-error class="flex flex-col items-start gap-3 rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm text-ink">
        <strong class="font-bold text-danger">No se pudo cargar el reporte.</strong>
        <span>No se perdió ningún dato. Revisa tu conexión e inténtalo de nuevo.</span>
        <x-ui.button variant="secondary" data-reports-retry>Intentar de nuevo</x-ui.button>
    </div>

    <div hidden class="space-y-6" data-reports-content>
        <div class="grid gap-6 min-[880px]:grid-cols-12">
            <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6 min-[880px]:col-span-5" aria-labelledby="reports-figures-title">
                <h2 id="reports-figures-title" class="text-lg font-bold text-ink md:text-xl" data-reports-figures-title>Cifras del mes</h2>
                <dl class="mt-4 grid grid-cols-2 gap-2" data-reports-figures></dl>
                <p class="mt-4 flex flex-wrap gap-2" aria-hidden="true">
                    <span class="inline-flex items-center gap-1 rounded-full border border-transparent bg-success-soft px-2.5 py-0.5 text-min font-bold text-success">▲ favorable</span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-transparent bg-warning-soft px-2.5 py-0.5 text-min font-bold text-warning">▲ desfavorable</span>
                    <span class="inline-flex items-center gap-1 rounded-full border border-line bg-surface px-2.5 py-0.5 text-min font-bold text-muted">= sin cambio</span>
                </p>
                <p class="mt-3 text-sm text-muted" data-reports-figures-note>"Sin cerrar": fecha ya pasada y siguen asignadas o reprogramadas.</p>
            </section>

            <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6 min-[880px]:col-span-7" aria-labelledby="reports-week-title">
                <h2 id="reports-week-title" class="text-lg font-bold text-ink md:text-xl">Cuándo llegan las citas</h2>
                <p hidden class="mt-4 text-center text-sm text-muted" data-reports-week-empty></p>
                <table class="mt-4 w-full border-separate border-spacing-0.5 text-center text-sm" data-reports-week>
                    <caption class="sr-only" data-reports-week-caption>Citas no canceladas del mes por hora de inicio y día de la semana, con totales por hora y por día</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="text-min font-bold text-muted"><span class="sr-only">Hora</span></th>
                            @foreach (['lunes' => 'Lun', 'martes' => 'Mar', 'miércoles' => 'Mié', 'jueves' => 'Jue', 'viernes' => 'Vie', 'sábado' => 'Sáb', 'domingo' => 'Dom'] as $day => $abbreviation)
                                <th scope="col" class="text-min font-bold text-muted"><abbr title="{{ $day }}" class="no-underline">{{ $abbreviation }}</abbr></th>
                            @endforeach
                            <th scope="col" class="text-min font-bold text-muted">Total</th>
                        </tr>
                    </thead>
                    <tbody data-reports-week-body></tbody>
                    <tfoot data-reports-week-foot></tfoot>
                </table>
                <p class="mt-3 flex flex-wrap items-center gap-3 text-min text-muted" aria-hidden="true" data-reports-week-legend>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full border border-line bg-surface"></span>0</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-primary/25"></span>1</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-primary/55"></span>2</span>
                    <span class="inline-flex items-center gap-1"><span class="inline-block h-3 w-3 rounded-full bg-primary"></span>3 o más</span>
                    <span>Contorno: el día y la hora con más citas</span>
                </p>
                <p class="mt-2 text-sm text-muted">Sin contar canceladas. Las horas van de la primera a la última franja con citas del mes.</p>
            </section>
        </div>

        <div class="grid gap-6 min-[880px]:grid-cols-12">
            <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6 min-[880px]:col-span-7" aria-labelledby="reports-treatments-title">
                <h2 id="reports-treatments-title" class="text-lg font-bold text-ink md:text-xl">Tratamientos más solicitados</h2>
                <ol class="mt-4 space-y-4" data-reports-treatments></ol>
                <p hidden class="mt-4 text-center text-sm text-muted" data-reports-treatments-empty></p>
                <p class="mt-3 text-sm text-muted">Sin contar canceladas.</p>
            </section>

            <section class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-6 min-[880px]:col-span-5" aria-labelledby="reports-staff-title">
                <h2 id="reports-staff-title" class="text-lg font-bold text-ink md:text-xl">Carga por miembro del staff</h2>
                <ol class="mt-4 space-y-4" data-reports-staff></ol>
                <p hidden class="mt-4 text-center text-sm text-muted" data-reports-staff-empty></p>
            </section>
        </div>
    </div>

    <template data-reports-stat-template>
        <x-ui.stat variant="comparison" label="">
            <span data-stat-figure></span>
            <x-slot:trend><span data-stat-trend></span></x-slot:trend>
        </x-ui.stat>
    </template>

    <template data-reports-rank-template>
        <li class="space-y-1.5">
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-sm font-semibold text-ink" data-rank-name></span>
                <span class="shrink-0 text-sm font-bold text-ink" data-rank-value></span>
            </div>
            <span class="block h-2 rounded-full bg-line" aria-hidden="true"><span class="block h-2 rounded-full bg-primary" data-rank-bar></span></span>
        </li>
    </template>
</div>

@vite('resources/js/pages/reportes/index.js')
@endsection
