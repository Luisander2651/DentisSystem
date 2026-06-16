@extends('layouts.admin')

@section('title', 'Usuarios - Dentissa')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <x-ui.h1 as="h2" class="text-xl! text-slate-900">Usuarios</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Administra el acceso y roles del personal de la clinica.</p>
        </div>

        <button type="button" data-create-user-open class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#E91E63] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-[#E91E63]/20 transition hover:bg-[#d61b5b] active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7v14"/></svg>
            Nuevo usuario
        </button>
    </div>

    {{-- Counters Section --}}
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total Personal</p>
            <p class="mt-2 text-xl font-semibold text-slate-900" data-users-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Administradores</p>
            <p class="mt-2 text-xl font-semibold text-[#B5114A]" data-users-admin-count>0</p>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Asistentes</p>
            <p class="mt-2 text-xl font-semibold text-sky-600" data-users-asistent-count>0</p>
        </div>
    </div>

    {{-- Info Card --}}
    <article class="rounded-3xl border border-[#F5C2D6] bg-[#FFF7FA] p-5">
        <div class="flex items-start gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-[#B5114A] shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-[#B5114A]">Gestión de Seguridad</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Desde este panel puedes controlar quién tiene acceso al sistema. Asegúrate de asignar los roles correctamente para proteger la información de la clínica.</p>
            </div>
        </div>
    </article>

    {{-- Feedback and List --}}
    <div id="users-error" class="hidden rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700 text-center"></div>
    
    <div id="users-loading" class="hidden rounded-3xl border border-slate-200 bg-white p-12 text-sm text-slate-500 shadow-sm text-center font-bold">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-[#B5114A] border-t-transparent mb-3"></div>
        <p>Sincronizando personal...</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-users-list id="users-table"></div>

    <div id="users-empty" class="hidden rounded-3xl border border-slate-200 bg-white p-12 text-center shadow-sm">
        <p class="text-sm font-bold text-slate-400 italic">No hay usuarios registrados para mostrar.</p>
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

<script>
    (function () {
        const errorBox = document.getElementById('users-error');
        const loadingBox = document.getElementById('users-loading');
        const emptyBox = document.getElementById('users-empty');
        const userList = document.querySelector('[data-users-list]');
        
        const countAll = document.querySelector('[data-users-count]');
        const countAdmin = document.querySelector('[data-users-admin-count]');
        const countAsistent = document.querySelector('[data-users-asistent-count]');

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

        function normalizeRole(roleId) {
            switch (roleId) {
                case "admin": return 'Administrador';
                case "asistent": return 'Asistente';
                default: return roleId;
            }
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

            records.forEach(user => {
                if (user.role_id === 'admin') adminCount++;
                else if (user.role_id === 'asistent') asistentCount++;
            });

            if (countAll) countAll.textContent = String(records.length);
            if (countAdmin) countAdmin.textContent = String(adminCount);
            if (countAsistent) countAsistent.textContent = String(asistentCount);

            if (emptyBox) emptyBox.classList.toggle('hidden', records.length !== 0);

            userList.innerHTML = records.map(function (user) {
                const id = user.id ?? '';
                const firstName = user.first_name ?? '';
                const lastName = user.last_name ?? '';
                const fullName = `${firstName} ${lastName}`.trim();
                const email = user.email ?? 'Sin correo';
                const roleId = user.role_id ?? '';
                const roleLabel = normalizeRole(roleId);
                const status = user.status ?? '';
                const statusLabel = normalizeStatus(status);
                
                const statusClasses = status === 'active'
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-slate-100 text-slate-500';

                const roleClasses = roleId === 'admin'
                    ? 'bg-[#FDF1F6] text-[#B5114A]'
                    : 'bg-sky-50 text-sky-700';

                const initial = (firstName.charAt(0) || lastName.charAt(0) || 'U').toUpperCase();

                return [
                    '<article class="group rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:border-[#F5C2D6] hover:shadow-md">',
                        '<div class="flex items-start justify-between gap-4">',
                            '<div class="flex items-center gap-3">',
                                '<div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl ' + roleClasses + ' text-base font-bold transition-transform group-hover:scale-105">', escapeHtml(initial), '</div>',
                                '<div>',
                                    '<h3 class="text-base font-semibold text-slate-900 break-words group-hover:text-[#B5114A] transition-colors">', escapeHtml(fullName), '</h3>',
                                    '<p class="text-[10px] font-bold uppercase tracking-wider ' + (roleId === 'admin' ? 'text-[#B5114A]' : 'text-sky-700') + '">', escapeHtml(roleLabel), '</p>',
                                '</div>',
                            '</div>',
                            '<span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest ' + statusClasses + '">', escapeHtml(statusLabel), '</span>',
                        '</div>',
                        
                        '<div class="mt-5">',
                            '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600 font-bold group-hover:bg-[#FFF7FA] transition-colors">',
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
                                '<span class="break-all">', escapeHtml(email), '</span>',
                            '</div>',
                        '</div>',

                        '<div class="mt-5 flex gap-2">',
                            '<button type="button" data-user-edit data-user-id="', escapeHtml(id), '" data-user-first-name="', escapeHtml(firstName), '" data-user-last-name="', escapeHtml(lastName), '" data-user-role-id="', escapeHtml(roleId), '" data-user-status="', escapeHtml(status), '" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 active:scale-95 shadow-sm">Editar</button>',
                            '<button type="button" data-user-delete data-user-id="', escapeHtml(id), '" data-user-first-name="', escapeHtml(firstName), '" data-user-last-name="', escapeHtml(lastName), '" class="rounded-xl border border-red-100 bg-red-50 p-2.5 text-red-600 transition hover:bg-red-600 hover:text-white active:scale-95 shadow-sm">',
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
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
                    const message = payload.error || payload.message || 'No se pudieron cargar los usuarios.';
                    showError(message);
                    renderUserCards([]);
                    return;
                }

                const records = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
                renderUserCards(records);
            } catch (error) {
                showError('Error de conexion. Intentalo de nuevo.');
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
