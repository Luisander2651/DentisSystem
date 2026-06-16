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
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <x-ui.h1 as="h1" class="text-xl! sm:text-3xl! text-slate-900 font-semibold tracking-tight">
                ¡Hola, {{ $user ? ($user->first_name ?? 'Usuario') : 'Usuario' }}!
            </x-ui.h1>
            <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                @if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
                    Bienvenido al centro de control de Dentissa.
                @elseif (strtolower($userRole) === 'asistente')
                    Listos para gestionar las sonrisas de hoy.
                @else
                    Tu salud dental, siempre a un clic de distancia.
                @endif
            </p>
        </div>

        @if ($user)
            <div class="flex items-center gap-3 rounded-3xl border border-slate-200 bg-white p-2 pr-4 shadow-sm">
                <div class="h-10 w-10 rounded-2xl bg-[#FDF1F6] flex items-center justify-center text-[#B5114A] font-bold">
                    {{ substr($user->first_name ?? 'U', 0, 1) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900 leading-tight">{{ $user->email ?? 'N/A' }}</p>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#B5114A]">{{ $rawRole ?: $userRole }}</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Dashboard Content by Role -->
    @if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
        <!-- Admin Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pacientes</p>
                        <p class="mt-2 text-xl font-semibold text-slate-900">-</p>
                    </div>
                    <div class="rounded-2xl bg-pink-50 p-3 text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Citas 30D</p>
                        <p class="mt-2 text-xl font-semibold text-slate-900">-</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50 p-3 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Usuarios</p>
                        <p class="mt-2 text-xl font-semibold text-slate-900">-</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Estado</p>
                        <p class="mt-2 text-xl font-semibold text-emerald-600">En línea</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-600">
                        <span class="relative flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Recent Activity Placeholder -->
            <div class="lg:col-span-8 rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-900 mb-6">Actividad Reciente</h2>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="rounded-full bg-slate-50 p-4 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 8v4l3 3" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-400">No hay actividad reciente para mostrar.</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="lg:col-span-4 space-y-6">
                <div class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-8">
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-[#B5114A] mb-6">Accesos Rápidos</h2>
                    <div class="space-y-3">
                        <button type="button" data-create-patient-open class="w-full group flex items-center justify-between rounded-2xl bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition-all hover:scale-[1.02]">
                            Nuevo Paciente
                            <span class="rounded-xl bg-pink-100 p-2 text-[#E91E63]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </button>
                        <a href="/agenda" class="w-full group flex items-center justify-between rounded-2xl bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition-all hover:scale-[1.02]">
                            Agendar Cita
                            <span class="rounded-xl bg-blue-50 p-2 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </a>
                        <button type="button" data-create-user-open class="w-full group flex items-center justify-between rounded-2xl bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition-all hover:scale-[1.02]">
                            Nuevo Usuario
                            <span class="rounded-xl bg-emerald-100 p-2 text-emerald-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @elseif (strtolower($userRole) === 'asistente')
        <!-- Assistant Dashboard (match admin visual style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Citas Hoy</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">-</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50 p-3 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Pacientes Registrados</p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">-</p>
                    </div>
                    <div class="rounded-2xl bg-pink-50 p-3 text-[#B5114A] transition-colors group-hover:bg-[#B5114A] group-hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-8 mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-[#B5114A] mb-6">Acciones para hoy</h2>
            <div class="space-y-3">
                <button type="button" data-create-patient-open class="w-full group flex items-center justify-between rounded-2xl bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition-all hover:scale-[1.02]">
                    Registrar Nuevo Paciente
                    <span class="rounded-xl bg-pink-100 p-2 text-[#E91E63] shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                    </span>
                </button>
                <a href="/agenda" class="w-full group flex items-center justify-between rounded-2xl bg-white p-4 text-sm font-semibold text-slate-900 shadow-sm transition-all hover:scale-[1.02]">
                    Ver Agenda
                    <span class="rounded-xl bg-blue-50 p-2 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12h14m-7-7v14"/></svg>
                    </span>
                </a>
            </div>
        </div>

    @else
        <!-- Patient Dashboard (match admin visual style) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="group rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md relative overflow-hidden">
                <div class="absolute top-2 right-2 p-2 opacity-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                        <path d="M16 2v4M8 2v4M3 10h18" />
                    </svg>
                </div>
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Próxima Cita</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">Aún no agendada</p>
                <p class="text-xs text-slate-500 mt-2">Te avisaremos cuando haya disponibilidad.</p>
            </div>

            <div class="group rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Tu Historial</p>
                <p class="mt-1 text-lg font-semibold text-slate-900">Consultar Expediente</p>
                <a href="/expedientes-clinicos" class="mt-3 inline-block text-sm font-semibold text-[#B5114A] hover:underline">Ver detalles →</a>
            </div>
        </div>

        <div class="mt-6">
            <a href="/agenda" class="flex w-full items-center justify-center rounded-2xl border border-pink-100 py-3 text-lg font-semibold text-[#E91E63] bg-white shadow-sm transition-all hover:bg-pink-50 active:scale-95">
                Agendar nueva cita
            </a>
        </div>
    @endif
</div>

<!-- Create Patient Modal (for Admin and Assistant) -->
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin' || strtolower($userRole) === 'asistente')
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
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin' || strtolower($userRole) === 'asistente')
    @vite('resources/js/pages/patients/create-patient.js')
@endif
@if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
    @vite('resources/js/pages/usuarios/create-user.js')
@endif
@endsection
