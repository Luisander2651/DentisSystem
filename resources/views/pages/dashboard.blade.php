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
<div class="max-w-7xl mx-auto">
    <!-- Welcome Section -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <x-ui.h1 as="h1" class="text-2xl! sm:text-3xl!">
                    Bienvenido,
                    @if ($user)
                        {{ $user->first_name ?? 'Usuario' }}
                    @else
                        Usuario
                    @endif
                </x-ui.h1>
                <p class="mt-2 text-sm text-slate-600">
                    @if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
                        Panel de administración - Dentissa
                    @elseif (strtolower($userRole) === 'asistente')
                        Panel de asistente - Dentissa
                    @else
                        Tu portal de paciente - Dentissa
                    @endif
                </p>
            </div>

            @if ($user)
                <div class="text-right">
                    <p class="text-sm font-medium text-slate-700">{{ $user->email ?? 'N/A' }}</p>
                    <p class="text-xs text-slate-500">Rol: <span class="font-semibold capitalize">{{ $rawRole ?: $userRole }}</span></p>
                </div>
            @endif
        </div>
    </div>

    <!-- Dashboard Content by Role -->
    @if (strtolower($userRole) === 'administrador' || strtolower($userRole) === 'admin')
        <!-- Admin Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Stats Cards -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Pacientes</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">-</p>
                    </div>
                    <div class="rounded-lg bg-[#FDF1F6] p-3 text-[#E91E63]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Citas Proximo 30D</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">-</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-3 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Usuarios</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">-</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Sistema</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-600">Online</p>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity / Quick Links -->
        <div class="space-y-6">
            <div id="dashboard-patients-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>
            
            <div id="dashboard-users-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Acciones Rapidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <button type="button" data-create-patient-open class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center cursor-pointer">
                        + Nuevo Paciente
                    </button>
                    <a href="#" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center">
                        + Nueva Cita
                    </a>
                    <button type="button" data-create-user-open class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center cursor-pointer">
                        + Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>

    @elseif (strtolower($userRole) === 'asistente')
        <!-- Assistant Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Citas Hoy</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">-</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-3 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Pacientes</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">-</p>
                    </div>
                    <div class="rounded-lg bg-[#FDF1F6] p-3 text-[#E91E63]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div id="dashboard-patients-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>

            <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Acciones Rapidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button type="button" data-create-patient-open class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center cursor-pointer">
                        + Registrar Paciente
                    </button>
                    <a href="#" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center">
                        + Agendar Cita
                    </a>
                </div>
            </div>
        </div>

    @else
        <!-- Patient Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Proxima Cita</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">-</p>
                        <p class="text-xs text-slate-500 mt-1">Sin citas programadas</p>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-3 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase">Tu Expediente</p>
                        <p class="mt-2 text-lg font-bold text-slate-900">-</p>
                        <p class="text-xs text-slate-500 mt-1">Ver informacion medica</p>
                    </div>
                    <div class="rounded-lg bg-[#FDF1F6] p-3 text-[#E91E63]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Acciones Rapidas</h2>
            <div class="grid grid-cols-1 gap-3">
                <a href="#" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-100 transition-colors text-center">
                    Agendar Nueva Cita
                </a>
            </div>
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
