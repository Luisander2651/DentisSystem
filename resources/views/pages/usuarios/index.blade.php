@extends('layouts.admin')

@section('title', 'Usuarios - Dentissa')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
           <x-ui.h1 class="text-slate-900 text-xl">Usuarios</x-ui.h1>
            <p class="mt-2 text-sm text-slate-500">Listado de usuarios registrados en el sistema.</p>
        </div>

        <x-ui.button variant="primary" type="button" class="cursor-pointer" data-create-user-open>
            Agregar un nuevo usuario
        </x-ui.button>
    </div>

    <div id="users-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    <x-ui.table
        table-id="users-table"
        :headers="[
            'first_name' => 'Nombre',
            'last_name' => 'Apellido',
            'email' => 'Correo',
            'role_id' => 'Rol',
            'status' => 'Estado',
        ]"
        :rows="[]"
        empty-message="Sin datos"
        :actions="true"
    />

    <x-ui.confirm-delete-modal
        modal-id="users-delete-modal"
        message-prefix="Esta segura que desea eliminar"
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
        const tableId = 'users-table';

        function showError(message) {
            if (!errorBox) {
                return;
            }

            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function hideError() {
            if (!errorBox) {
                return;
            }

            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }

        function normalizeRole(roleId) {
            switch (roleId) {
                case "admin":
                    return 'Administrador';
                case "asistent":
                    return 'Asistente';
            }
        }

        function normalizeStatus(status) {
            switch (status) {
                case "active":
                    return 'Activo';
                case "inactive":
                    return 'Inactivo';
                default:
                    return '-';
            }
        }

        function normalizeUsers(payload) {
            const records = Array.isArray(payload?.data)
                ? payload.data
                : (Array.isArray(payload) ? payload : []);

            return records.map(function (user) {
                const firstName = user.first_name ?? '';
                const lastName = user.last_name ?? '';
                const role = normalizeRole(user.role_id);

                return {
                    id: user.id ?? null,
                    full_name: `${firstName} ${lastName}`.trim(),
                    first_name: user.first_name ?? '-',
                    first_name_value: user.first_name ?? '',
                    last_name: user.last_name ?? '-',
                    last_name_value: user.last_name ?? '',
                    email: user.email ?? '-',
                    role_id: role ?? '-',
                    role_value: user.role_id ?? '',
                    status: normalizeStatus(user.status) ?? '-',
                    status_value: user.status ?? '',
                };
            });
        }

        async function loadUsers() {
            hideError();

            try {
                const response = await fetch('{{ url('/api/v1/users') }}', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json().catch(function () {
                    return {};
                });

                if (!response.ok) {
                    const message = payload.error || payload.message || 'No se pudieron cargar los usuarios.';
                    showError(message);
                    window.renderUiTable(tableId, []);
                    return;
                }

                const rows = normalizeUsers(payload);
                window.renderUiTable(tableId, rows);
            } catch (error) {
                showError('Error de conexion. Intentalo de nuevo.');
                window.renderUiTable(tableId, []);
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
