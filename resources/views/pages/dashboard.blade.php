@php
    // Spec 020: ShowDashboardPageController hands over the role, the time of the clinic, the month
    // in course and what the role may do. The data comes later from the API (dashboard.js).
    $user = auth()->user();
    $role = $dashboardRole ?? 'paciente';
    $isStaff = in_array($role, ['administrador', 'asistente', 'doctor'], true);
    $isAdmin = $role === 'administrador';
    $can = $dashboardCan ?? [];
    $now = $dashboardNow ?? now()->toImmutable();

    $weekdays = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $monthName = $months[$now->month - 1];
    $todayLabel = $weekdays[$now->dayOfWeek].' '.$now->day.' de '.$monthName.' · '.$now->format('H:i');

    $canManageAgenda = ($can['create_appointment'] ?? false) || ($can['cancel'] ?? false);
    $canComplete = $can['complete'] ?? false;
    $trackingScope = $isAdmin ? 'all' : ($role === 'doctor' ? 'own' : 'none');

    $section = 'rounded-card border border-line bg-surface p-4 shadow-sm md:p-6';
    $heading = 'text-lg font-bold text-ink';
    $loading = 'text-sm text-muted';
    $empty = 'text-sm text-muted';
    $link = 'inline-flex min-h-control items-center text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4';
@endphp

@extends($isStaff ? 'layouts.admin' : 'layouts.patient')

@section('title', 'Inicio - Dentissa')

@section('content')
@if ($isStaff)
<div
    class="space-y-6"
    data-dashboard
    data-dashboard-role="{{ $role }}"
    data-dashboard-now="{{ $now->format('H:i') }}"
    data-dashboard-can-complete="{{ $canComplete ? 'true' : 'false' }}"
    data-dashboard-can-cancel="{{ ($can['cancel'] ?? false) ? 'true' : 'false' }}"
    data-dashboard-can-register="{{ ($can['register'] ?? false) ? 'true' : 'false' }}"
    @if ($isAdmin) data-current-month="{{ $dashboardMonth }}" @endif
>
    <section class="{{ $section }} flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="dashboard-title">
        <div>
            <h1 id="dashboard-title" class="text-2xl font-bold text-ink md:text-3xl">¡Hola, {{ $user?->first_name }}!</h1>
            <p class="mt-1 text-sm text-muted">{{ $todayLabel }}</p>
        </div>
        @if ($isAdmin)
            <x-ui.button variant="primary" class="w-full whitespace-nowrap sm:w-auto" data-create-appointment-open>
                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Nueva cita
            </x-ui.button>
        @endif
    </section>

    {{-- Below 1024 px the two columns melt into one list ordered as design.md says (c5). --}}
    <div class="flex flex-col gap-6 lg:grid lg:grid-cols-12 lg:items-start">
        <div class="contents lg:col-span-7 lg:flex lg:flex-col lg:gap-6">
            <section class="{{ $section }} order-1" aria-labelledby="dashboard-next-title" data-dashboard-section="next">
                <h2 id="dashboard-next-title" class="flex items-center gap-2 text-min font-bold uppercase tracking-wide text-muted">
                    <span class="size-2 rounded-full bg-primary" aria-hidden="true"></span>Siguiente cita
                </h2>
                <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
                <p class="{{ $empty }} mt-3" data-dashboard-state="empty" hidden>No quedan citas por atender hoy.</p>
                @include('pages.dashboard.section-error')
                <x-dashboard.next-appointment class="mt-3" :can-complete="$canComplete" data-dashboard-content hidden />
            </section>

            <section class="{{ $section }} order-4" aria-labelledby="dashboard-agenda-title" data-dashboard-section="agenda">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="dashboard-agenda-title" class="{{ $heading }}">Agenda de hoy</h2>
                    <a href="{{ route('agenda.index') }}" class="{{ $link }}">Abrir la agenda</a>
                </div>
                <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
                <p class="{{ $empty }} mt-3" data-dashboard-state="empty" hidden>No hay citas para hoy.</p>
                @include('pages.dashboard.section-error')
                <ol class="mt-3 space-y-1" data-dashboard-content hidden></ol>
            </section>
        </div>

        <div class="contents lg:col-span-5 lg:flex lg:flex-col lg:gap-6">
            <section class="{{ $section }} order-2" aria-labelledby="dashboard-day-title" data-dashboard-section="day">
                <h2 id="dashboard-day-title" class="{{ $heading }}">Cómo va el día</h2>
                <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
                @include('pages.dashboard.section-error')
                <div class="mt-4 space-y-4" data-dashboard-content hidden>
                    <x-dashboard.day-progress />
                    <div class="flex flex-col gap-2 rounded-box border border-secondary bg-primary-soft px-4 py-3 text-sm text-ink" data-dashboard-pending-summary>
                        <a href="#dashboard-unclosed-title" class="flex items-center justify-between gap-3 font-semibold">
                            <span>Citas sin cerrar</span><span class="tabular-nums" data-dashboard-total="unclosed">0</span>
                        </a>
                        @if ($can['register'] ?? false)
                            <a href="#dashboard-without-tracking-title" class="flex items-center justify-between gap-3 font-semibold">
                                <span>Completadas sin seguimiento</span><span class="tabular-nums" data-dashboard-total="without-tracking">0</span>
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            <nav class="{{ $section }} order-3" aria-labelledby="dashboard-quick-title">
                <h2 id="dashboard-quick-title" class="{{ $heading }}">Acciones rápidas</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                    @if (! $isAdmin && ($can['create_appointment'] ?? false))
                        <li><x-ui.button class="w-full whitespace-nowrap" data-create-appointment-open data-dashboard-quick="new-appointment">Nueva cita</x-ui.button></li>
                    @endif
                    @if ($can['create_patient'] ?? false)
                        <li><x-ui.button class="w-full whitespace-nowrap" data-create-patient-open data-dashboard-quick="new-patient">Nuevo paciente</x-ui.button></li>
                    @endif
                    <li><x-ui.button class="w-full whitespace-nowrap" href="{{ route('records.index') }}?buscar=1" data-dashboard-quick="search">Buscar paciente</x-ui.button></li>
                    <li><x-ui.button class="w-full whitespace-nowrap" href="{{ route('agenda.index') }}" data-dashboard-quick="today">Agenda de hoy</x-ui.button></li>
                    @if ($can['register'] ?? false)
                        <li><x-ui.button class="w-full whitespace-nowrap" data-dashboard-quick="register" hidden>Registrar seguimiento</x-ui.button></li>
                    @endif
                </ul>
            </nav>

            @if ($isAdmin)
                <section class="{{ $section }} order-5" aria-labelledby="dashboard-month-title" data-dashboard-section="month">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="dashboard-month-title" class="{{ $heading }}">{{ mb_strtoupper(mb_substr($monthName, 0, 1)).mb_substr($monthName, 1) }}</h2>
                        <a href="{{ route('reportes.index') }}" class="{{ $link }}">Ver reportes</a>
                    </div>
                    <p class="mt-1 text-sm text-muted">Mes en curso: datos hasta hoy</p>
                    <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
                    @include('pages.dashboard.section-error')
                    <ul class="mt-4 divide-y divide-line" data-dashboard-content hidden>
                        @foreach (['appointments', 'new-patients', 'active-patients'] as $figure)
                            <li class="flex flex-wrap items-baseline gap-x-2 py-2" data-dashboard-month="{{ $figure }}"></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </div>

    <section class="{{ $section }}" aria-labelledby="dashboard-unclosed-title" data-dashboard-section="unclosed">
        <h2 id="dashboard-unclosed-title" class="{{ $heading }} flex items-center gap-2">Citas sin cerrar <span class="tabular-nums text-muted" data-dashboard-count></span></h2>
        <p class="mt-1 text-sm text-muted">De días anteriores, siguen asignadas o reprogramadas.</p>
        <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
        <p class="{{ $empty }} mt-3" data-dashboard-state="empty" hidden>No hay citas pendientes de cerrar.</p>
        @include('pages.dashboard.section-error')
        <div class="mt-4 space-y-4" data-dashboard-content hidden></div>
        <p class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted" data-dashboard-more hidden>
            <span data-dashboard-more-text></span>
            <a href="{{ route('agenda.index') }}" class="{{ $link }}">Ver en la agenda</a>
        </p>
    </section>

    @if ($can['register'] ?? false)
        <section class="{{ $section }}" aria-labelledby="dashboard-without-tracking-title" data-dashboard-section="without-tracking">
            <h2 id="dashboard-without-tracking-title" class="{{ $heading }} flex items-center gap-2">Completadas sin seguimiento <span class="tabular-nums text-muted" data-dashboard-count></span></h2>
            <p class="mt-1 text-sm text-muted">Falta registrar el seguimiento clínico.</p>
            <p class="{{ $loading }} mt-3" data-dashboard-state="loading">Cargando…</p>
            <p class="{{ $empty }} mt-3" data-dashboard-state="empty" hidden>Todas las citas completadas tienen seguimiento.</p>
            @include('pages.dashboard.section-error')
            <div class="mt-4 space-y-4" data-dashboard-content hidden></div>
            <p class="mt-4 text-sm text-muted" data-dashboard-more hidden><span data-dashboard-more-text></span></p>
        </section>
    @endif

    {{-- Templates that dashboard.js clones and fills with textContent (TM7). --}}
    <template data-dashboard-template="agenda-item">
        <li class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto] items-start gap-x-3 gap-y-1 rounded-box border-l-4 border-transparent py-2 pl-2 data-[next]:border-primary data-[past]:text-muted" data-dashboard-item>
            <span class="text-sm font-bold tabular-nums" data-item-time></span>
            <span class="min-w-0">
                <span class="block font-semibold text-ink" data-item-patient></span>
                <span class="sr-only" data-item-next-label hidden>(siguiente)</span>
                <span class="block text-sm text-muted" data-item-detail></span>
            </span>
            <span data-item-status></span>
        </li>
    </template>
    <template data-dashboard-template="now-line">
        <x-dashboard.now-line />
    </template>
    <template data-dashboard-template="pending-group">
        <x-dashboard.pending-group />
    </template>
    <template data-dashboard-template="unclosed-row">
        <li class="flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 gap-3">
                <span class="w-12 shrink-0 text-sm font-bold tabular-nums text-ink" data-item-time></span>
                <div class="min-w-0">
                    <p class="font-semibold text-ink" data-item-patient></p>
                    <p class="text-sm text-muted" data-item-detail></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 md:shrink-0">
                @if ($canComplete)
                    <x-ui.button class="whitespace-nowrap" data-complete-appointment-trigger>Completar</x-ui.button>
                @endif
                @if ($can['cancel'] ?? false)
                    <x-ui.button class="whitespace-nowrap" data-dashboard-cancel>Cancelar cita</x-ui.button>
                @endif
            </div>
        </li>
    </template>
    @if ($can['register'] ?? false)
        <template data-dashboard-template="without-tracking-row">
            <li class="flex flex-col gap-3 px-4 py-3 md:flex-row md:items-center md:justify-between">
                <div class="flex min-w-0 gap-3">
                    <span class="w-12 shrink-0 text-sm font-bold tabular-nums text-ink" data-item-time></span>
                    <div class="min-w-0">
                        <p class="font-semibold text-ink" data-item-patient></p>
                        <p class="text-sm text-muted" data-item-detail></p>
                    </div>
                </div>
                <x-ui.button class="whitespace-nowrap md:shrink-0" data-dashboard-register>Registrar seguimiento</x-ui.button>
            </li>
        </template>
    @endif
</div>

@if ($canManageAgenda)
    <x-calendar.create-appointment-modal :with-selectors="true" />
    <x-calendar.edit-appointment-modal />
@endif
<x-calendar.view-appointment-modal :tracking-scope="$trackingScope" :actor-id="(string) auth()->id()" />
@if ($can['cancel'] ?? false)
    <x-calendar.cancel-appointment-confirm />
@endif
@if ($canComplete)
    <x-calendar.complete-appointment-modal />
@endif
@if ($can['create_patient'] ?? false)
    <x-ui.create-patient-modal modal-id="patients-create-modal" />
@endif

@vite('resources/js/pages/dashboard.js')
@vite('resources/js/pages/agenda/view-appointment.js')
@if ($canManageAgenda)
    @vite('resources/js/pages/agenda/create-appointment.js')
@endif
@if ($can['cancel'] ?? false)
    @vite('resources/js/pages/agenda/cancel-appointment.js')
@endif
@if ($canComplete)
    @vite('resources/js/pages/agenda/complete-appointment.js')
@endif
@if ($can['create_patient'] ?? false)
    @vite('resources/js/pages/patients/create-patient.js')
@endif
@else
<div class="space-y-8">
    <x-ui.page-hero
        :title="'¡Hola, '.($user?->first_name ?? 'Usuario').'!'"
        description="Tu salud dental, siempre a un clic de distancia."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
            </svg>
        </x-slot:icon>

        @if ($user)
            <x-slot:actions>
                <div class="flex items-center gap-3 rounded-card border border-line bg-primary-soft p-2 pr-4 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-box bg-primary-soft font-bold text-ink">
                        {{ mb_substr($user->first_name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight text-ink">{{ $user->email ?? 'Sin correo' }}</p>
                        <p class="text-xs font-semibold text-muted" data-role-label="inicio">Paciente</p>
                    </div>
                </div>
            </x-slot:actions>
        @endif
    </x-ui.page-hero>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="group rounded-card border border-line bg-surface p-4 shadow-sm transition-all hover:shadow-md relative overflow-hidden">
            <div class="absolute top-2 right-2 p-2 opacity-8">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                    <path d="M16 2v4M8 2v4M3 10h18" />
                </svg>
            </div>
            <p class="text-xs font-semibold uppercase tracking-widest text-muted">Próxima Cita</p>
            <p class="mt-1 text-lg font-semibold text-ink">Aún no agendada</p>
            <p class="text-xs text-muted mt-2">Te avisaremos cuando haya disponibilidad.</p>
        </div>
    </div>
</div>

@vite('resources/js/pages/dashboard.js')
@endif
@endsection
