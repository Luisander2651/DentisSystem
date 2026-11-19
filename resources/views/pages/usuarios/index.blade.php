@extends('layouts.admin')

@section('title', 'Usuarios - Dentissa')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <x-ui.page-hero
        title="Usuarios"
        description="Administra el acceso y los roles del personal de la clínica."
    >
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2 4 5v6c0 5 3.4 9.74 8 11 4.6-1.26 8-6 8-11V5l-8-3z" />
            </svg>
        </x-slot:icon>

        <x-slot:actions>
            <x-ui.button variant="primary" data-create-user-open class="w-full sm:w-auto">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
                Nuevo usuario
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-hero>

    {{-- Counters Section --}}
    <div class="grid grid-cols-2 gap-2 md:gap-3 lg:grid-cols-4">
        <x-ui.stat label="Total"><span data-users-count>0</span></x-ui.stat>
        <x-ui.stat label="Administradores"><span data-users-admin-count>0</span></x-ui.stat>
        <x-ui.stat label="Asistentes"><span data-users-asistent-count>0</span></x-ui.stat>
        <x-ui.stat label="Doctores"><span data-users-doctor-count>0</span></x-ui.stat>
    </div>

    {{-- Info Card --}}
    <article class="hidden rounded-box border border-secondary bg-primary-soft p-4 md:block md:p-5">
        <div class="flex items-start gap-4">
            <div data-accent class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-primary">
                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Gestión de seguridad</p>
                <p class="mt-1 text-sm leading-6 text-ink">Desde este panel puedes controlar quién tiene acceso al sistema. Asegúrate de asignar los roles correctamente para proteger la información de la clínica.</p>
            </div>
        </div>
    </article>

    {{-- Feedback and List --}}
    <div id="users-error" role="alert" class="hidden rounded-box border border-danger bg-danger-soft px-4 py-3 text-center text-sm font-semibold text-danger"></div>
    
    <div id="users-loading" class="hidden rounded-card border border-line bg-surface p-12 text-sm text-muted shadow-sm text-center font-bold">
        <div class="mb-3 inline-block h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent" aria-hidden="true"></div>
        <p>Cargando usuarios...</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-users-list id="users-table"></div>

    <x-ui.pagination label="Páginas de usuarios" data-users-pagination />

    <div id="users-empty" class="hidden rounded-card border border-line bg-surface p-12 text-center shadow-sm">
        <p class="text-sm font-semibold text-muted">No hay usuarios registrados para mostrar.</p>
    </div>

    {{-- Modals --}}
    <x-ui.confirm-delete-modal
        modal-id="users-delete-modal"
        message-prefix="¿Está seguro de que desea eliminar a"
    />

    <x-ui.edit-user-modal
        modal-id="users-edit-modal"
    />

    <x-ui.create-user-modal
        modal-id="users-create-modal"
    />
</div>

@vite('resources/js/pages/usuarios/index.js')
@vite('resources/js/pages/usuarios/delete-user.js')
@vite('resources/js/pages/usuarios/edit-user.js')
@vite('resources/js/pages/usuarios/create-user.js')
@endsection
