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

<script nonce="{{ Vite::cspNonce() }}">
    (function () {
        const errorBox = document.getElementById('users-error');
        const loadingBox = document.getElementById('users-loading');
        const emptyBox = document.getElementById('users-empty');
        const userList = document.querySelector('[data-users-list]');
        
        const countAll = document.querySelector('[data-users-count]');
        const countAdmin = document.querySelector('[data-users-admin-count]');
        const countAsistent = document.querySelector('[data-users-asistent-count]');
        const countDoctor = document.querySelector('[data-users-doctor-count]');

        function showError(message) {
            if (!errorBox) return;
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function hideError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }

        function setLoading(isLoading) {
            if (loadingBox) loadingBox.classList.toggle('hidden', !isLoading);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // BR-16: the API returns the canonical literal the roles table stores
        // ('Administrador', 'Asistente', 'Doctor'), which is already the display label, so
        // the old admin -> Administrador translation table is gone.
        function isAdminRole(roleId) {
            return roleId === 'Administrador';
        }

        function normalizeStatus(status) {
            switch (status) {
                case "active": return 'Activo';
                case "inactive": return 'Inactivo';
                default: return status;
            }
        }

        function renderUserCards(records) {
            if (!userList) return;

            let adminCount = 0;
            let asistentCount = 0;
            let doctorCount = 0;

            records.forEach(user => {
                if (user.role_id === 'Administrador') adminCount++;
                else if (user.role_id === 'Asistente') asistentCount++;
                else if (user.role_id === 'Doctor') doctorCount++;
            });

            if (countAll) countAll.textContent = String(records.length);
            if (countAdmin) countAdmin.textContent = String(adminCount);
            if (countAsistent) countAsistent.textContent = String(asistentCount);
            if (countDoctor) countDoctor.textContent = String(doctorCount);

            if (emptyBox) emptyBox.classList.toggle('hidden', records.length !== 0);

            userList.innerHTML = records.map(function (user) {
                const id = user.id ?? '';
                const firstName = user.first_name ?? '';
                const lastName = user.last_name ?? '';
                const fullName = `${firstName} ${lastName}`.trim();
                const email = user.email ?? 'Sin correo';
                const roleId = user.role_id ?? '';
                const roleLabel = roleId || 'Sin rol';
                const status = user.status ?? '';
                const statusLabel = normalizeStatus(status);
                
                const statusClasses = status === 'active'
                    ? 'bg-success-soft text-success'
                    : 'bg-danger-soft text-danger';

                const roleClasses = isAdminRole(roleId)
                    ? 'bg-primary-soft text-ink'
                    : 'bg-info-soft text-info';

                const initial = (firstName.charAt(0) || lastName.charAt(0) || 'U').toUpperCase();

                return [
                    '<article class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                        '<div class="flex items-start justify-between gap-4">',
                            '<div class="flex items-center gap-3">',
                                '<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-box ' + roleClasses + ' text-base font-bold" aria-hidden="true">', escapeHtml(initial), '</div>',
                                '<div>',
                                    '<h2 class="text-base font-semibold text-ink break-words">', escapeHtml(fullName), '</h2>',
                                    '<p class="text-min font-bold text-muted">', escapeHtml(roleLabel), '</p>',
                                '</div>',
                            '</div>',
                            '<span class="shrink-0 rounded-full px-2.5 py-0.5 text-min font-bold ' + statusClasses + '">', escapeHtml(statusLabel), '</span>',
                        '</div>',
                        
                        '<div class="mt-5">',
                            '<div class="flex items-center gap-2 rounded-box bg-canvas px-4 py-3 text-sm text-muted">',
                                '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
                                '<span class="break-all">', escapeHtml(email), '</span>',
                            '</div>',
                        '</div>',

                        '<div class="mt-5 flex gap-2">',
                            '<button type="button" data-user-edit data-user-id="', escapeHtml(id), '" data-user-first-name="', escapeHtml(firstName), '" data-user-last-name="', escapeHtml(lastName), '" data-user-role-id="', escapeHtml(roleId), '" data-user-status="', escapeHtml(status), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar</button>',
                            '<button type="button" data-user-delete data-user-id="', escapeHtml(id), '" data-user-first-name="', escapeHtml(firstName), '" data-user-last-name="', escapeHtml(lastName), '" class="inline-flex size-control shrink-0 items-center justify-center rounded-control border border-field bg-surface text-ink transition-colors hover:bg-danger-soft hover:text-danger" aria-label="Eliminar a ', escapeHtml(fullName), '">',
                                '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
                            '</button>',
                        '</div>',
                    '</article>'
                ].join('');
            }).join('');
        }

        async function loadUsers() {
            hideError();
            setLoading(true);

            try {
                const response = await fetch('{{ url('/api/v1/users') }}', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = 'No se pudieron cargar los usuarios. Inténtalo de nuevo en un momento.';
                    showError(message);
                    renderUserCards([]);
                    return;
                }

                const records = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                renderUserCards(records);
            } catch (error) {
                showError('No hay conexión. Revisa tu red e inténtalo de nuevo.');
                renderUserCards([]);
            } finally {
                setLoading(false);
            }
        }

        window.usersPage = {
            reload: loadUsers,
            showError: showError,
            hideError: hideError,
        };

        document.addEventListener('DOMContentLoaded', loadUsers);
    })();
</script>

@vite('resources/js/pages/usuarios/delete-user.js')
@vite('resources/js/pages/usuarios/edit-user.js')
@vite('resources/js/pages/usuarios/create-user.js')
@endsection
