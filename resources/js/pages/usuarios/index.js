import { mountPagination, readListState, writeListState } from '../../ui/pagination';

(function () {
    const errorBox = document.getElementById('users-error');
    const loadingBox = document.getElementById('users-loading');
    const emptyBox = document.getElementById('users-empty');
    const userList = document.querySelector('[data-users-list]');

    const countAll = document.querySelector('[data-users-count]');
    const countAdmin = document.querySelector('[data-users-admin-count]');
    const countAsistent = document.querySelector('[data-users-asistent-count]');
    const countDoctor = document.querySelector('[data-users-doctor-count]');

    // Spec 028 (CA1, CA5, CA8, CA9): the page comes from the address and goes to the server, which
    // answers one page with the counts by role of the whole staff.
    let currentPage = readListState(window.location.search).pagina;
    let requestNumber = 0;

    const pagination = mountPagination(document.querySelector('[data-users-pagination]'), {
        noun: 'usuarios',
        onChange: function (page) {
            currentPage = page;
            loadUsers({ announcePage: true });
        },
        focusTarget: document.querySelector('main h1'),
    });

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

    function renderCounters(counts) {
        if (!counts) {
            return;
        }

        if (countAll) countAll.textContent = String(counts.total ?? 0);
        if (countAdmin) countAdmin.textContent = String(counts.administrador ?? 0);
        if (countAsistent) countAsistent.textContent = String(counts.asistente ?? 0);
        if (countDoctor) countDoctor.textContent = String(counts.doctor ?? 0);
    }

    function renderUserCards(records) {
        if (!userList) return;

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

    async function loadUsers(options = {}) {
        const thisRequest = ++requestNumber;

        hideError();
        setLoading(true);

        try {
            const response = await fetch('/api/v1/users?page=' + encodeURIComponent(String(currentPage)), {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));

            if (thisRequest !== requestNumber) {
                return;
            }

            if (!response.ok) {
                const message = 'No se pudieron cargar los usuarios. Inténtalo de nuevo en un momento.';
                showError(message);
                renderUserCards([]);
                pagination.render(null);
                return;
            }

            const meta = payload?.meta ?? null;

            currentPage = meta?.page ?? 1;
            renderCounters(meta?.counts);
            renderUserCards(Array.isArray(payload?.data) ? payload.data : []);
            pagination.render(meta);
            writeListState({ pagina: currentPage, busqueda: '', estado: '' });

            if (options.announcePage) {
                pagination.announcePage(meta);
            }
        } catch (error) {
            if (thisRequest !== requestNumber) {
                return;
            }

            showError('No hay conexión. Revisa tu red e inténtalo de nuevo.');
            renderUserCards([]);
            pagination.render(null);
        } finally {
            if (thisRequest === requestNumber) {
                setLoading(false);
            }
        }
    }

    window.usersPage = {
        reload: function () {
            return loadUsers();
        },
        showError: showError,
        hideError: hideError,
    };

    document.addEventListener('DOMContentLoaded', function () {
        loadUsers();
    });
})();
