@php
    $user = auth()->user();
    $rawRole = null;

    if ($user && method_exists($user, 'getRole')) {
        $rawRole = $user->getRole();
    } elseif ($user && isset($user->role)) {
        $rawRole = $user->role;
    }

    if (is_object($rawRole)) {
        $rawRole = $rawRole->name ?? (method_exists($rawRole, 'toArray') ? data_get($rawRole->toArray(), 'name') : null);
    } elseif (is_array($rawRole)) {
        $rawRole = data_get($rawRole, 'name');
    }

    $userRole = strtolower((string) ($rawRole ?: 'patient'));

    $layouts = [
        'administrador' => 'layouts.admin',
        'admin' => 'layouts.admin',
        'asistente' => 'layouts.admin',
        'doctor' => 'layouts.admin',
        'patient' => 'layouts.patient',
        'paciente' => 'layouts.patient',
    ];

    $layoutName = $layouts[$userRole] ?? 'layouts.patient';
@endphp

@extends($layoutName)

@section('title', 'Dashboard - Dentissa')

@section('content')
<div class="space-y-8">
    <!-- Welcome Section -->
    <x-ui.page-hero
        :title="'¡Hola, '.($user ? ($user->first_name ?? 'Usuario') : 'Usuario').'!'"
        :description="strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin' ? 'Bienvenido al centro de control de Dentissa.' : (strtolower($userRole) === 'asistente' ? 'Listos para gestionar las sonrisas de hoy.' : (strtolower($userRole) === 'doctor' ? 'Consulta los expedientes clínicos de los pacientes.' : 'Tu salud dental, siempre a un clic de distancia.'))"
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
                        {{ substr($user->first_name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold leading-tight text-ink">{{ $user->email ?? 'Sin correo' }}</p>
                        <p class="text-xs font-semibold text-muted" data-role-label="inicio">{{ ['administrador' => 'Administrador', 'admin' => 'Administrador', 'asistente' => 'Asistente', 'doctor' => 'Doctor'][strtolower($userRole)] ?? 'Paciente' }}</p>
                    </div>
                </div>
            </x-slot:actions>
        @endif
    </x-ui.page-hero>

    <!-- Dashboard Content by Role -->
    @if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
        <!-- Admin Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Pacientes</p>
                        <p class="mt-2 text-xl font-semibold text-ink">-</p>
                    </div>
                    <div class="rounded-box bg-primary-soft p-3 text-ink transition-colors">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Citas 30D</p>
                        <p class="mt-2 text-xl font-semibold text-ink">-</p>
                    </div>
                    <div class="rounded-box bg-primary-soft p-3 text-ink transition-colors">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Usuarios</p>
                        <p class="mt-2 text-xl font-semibold text-ink">-</p>
                    </div>
                    <div class="rounded-box bg-primary-soft p-3 text-ink transition-colors">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Estado</p>
                        <p class="mt-2 text-xl font-semibold text-success">En línea</p>
                    </div>
                    <div class="rounded-box bg-primary-soft p-3 text-ink">
                        <span class="relative flex h-3 w-3" aria-hidden="true">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-primary"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Recent Activity Placeholder -->
            <div class="lg:col-span-8 rounded-card border border-line bg-surface p-8 shadow-sm">
                <h2 class="text-xl font-semibold text-ink mb-6">Actividad Reciente</h2>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="rounded-full bg-canvas p-4 mb-4">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-muted">No hay actividad reciente para mostrar.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lg:col-span-4 space-y-6">
                <div class="rounded-card border border-secondary bg-primary-soft p-8 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-ink mb-6">Accesos Rápidos</h2>
                    <div class="space-y-3">
                        <button type="button" data-create-patient-open data-pressable class="w-full group flex min-h-control items-center justify-between rounded-box bg-surface p-4 text-sm font-semibold text-ink shadow-sm transition-all hover:scale-[1.02]">
                            Nuevo Paciente
                            <span class="rounded-control bg-primary-soft p-2 text-ink">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </button>
                        <a href="/agenda" class="w-full group flex items-center justify-between rounded-box bg-surface p-4 text-sm font-semibold text-ink shadow-sm transition-all hover:scale-[1.02]">
                            Agendar Cita
                            <span class="rounded-control bg-info-soft p-2 text-info">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </a>
                        <button type="button" data-create-user-open data-pressable class="w-full group flex min-h-control items-center justify-between rounded-box bg-surface p-4 text-sm font-semibold text-ink shadow-sm transition-all hover:scale-[1.02]">
                            Nuevo Usuario
                            <span class="rounded-control bg-success-soft p-2 text-success">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @elseif (strtolower($userRole) === 'asistente')
        <!-- Assistant Dashboard (match admin visual style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Citas Hoy</p>
                        <p class="mt-2 text-2xl font-semibold text-ink">-</p>
                    </div>
                    <div class="rounded-box bg-info-soft p-3 text-info transition-colors">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="group rounded-card border border-line bg-surface p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-muted">Pacientes Registrados</p>
                        <p class="mt-2 text-2xl font-semibold text-ink">-</p>
                    </div>
                    <div class="rounded-box bg-primary-soft p-3 text-ink transition-colors">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-card border border-secondary bg-primary-soft p-8 mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-ink mb-6">Acciones para hoy</h2>
            <div class="space-y-3">
                <a href="/expedientes-clinicos" class="w-full group flex items-center justify-between rounded-box bg-surface p-4 text-sm font-semibold text-ink shadow-sm transition-all hover:scale-[1.02]">
                    Ver Expedientes Clínicos
                    <span class="rounded-control bg-primary-soft p-2 text-ink shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 12h14m-7-7v14"/></svg>
                    </span>
                </a>
            </div>
        </div>

    @elseif (strtolower($userRole) === 'doctor')
        <!-- Doctor Dashboard: read-only access to the records (spec 014, CA4) -->
        <div class="rounded-card border border-secondary bg-primary-soft p-8">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-ink mb-6">Acciones para hoy</h2>
            <div class="space-y-3">
                <a href="/expedientes-clinicos" class="w-full group flex items-center justify-between rounded-box bg-surface p-4 text-sm font-semibold text-ink shadow-sm transition-all hover:scale-[1.02]">
                    Ver Expedientes Clínicos
                    <span class="rounded-control bg-primary-soft p-2 text-ink shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 12h14m-7-7v14"/></svg>
                    </span>
                </a>
            </div>
        </div>

    @else
        <!-- Patient Dashboard (match admin visual style) -->
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
    @endif
</div>

<!-- Create Patient Modal (for Admin only; spec 014, CA15) -->
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
    <x-ui.create-patient-modal
        modal-id="patients-create-modal"
    />
@endif

<!-- Create User Modal (for Admin only) -->
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
    <x-ui.create-user-modal
        modal-id="users-create-modal"
    />
@endif

@vite('resources/js/pages/dashboard.js')
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
    @vite('resources/js/pages/patients/create-patient.js')
@endif
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
    @vite('resources/js/pages/usuarios/create-user.js')
@endif
@endsection
