import { mountPagination, readListState, writeListState } from '../../ui/pagination';

(function () {
    if (window.__patientsIndexInit) {
        return;
    }

    window.__patientsIndexInit = true;

    const errorBox = document.getElementById('patients-error');
    const loadingBox = document.getElementById('patients-loading');
    const emptyBox = document.getElementById('patients-empty');
    const patientsList = document.getElementById('patients-list');
    const searchInput = document.getElementById('patient-search');
    const statusFilter = document.querySelector('[data-patients-status-filter]');

    const countTotal = document.querySelector('[data-patients-total-count]');
    const countActive = document.querySelector('[data-patients-active-count]');
    const countInactive = document.querySelector('[data-patients-inactive-count]');

    const STATUSES = ['active', 'inactive'];
    const SEARCH_DELAY_MS = 300;
    const initialState = readListState(window.location.search);

    // Spec 028 (CA4, CA8, CA9): the page, the search and the filter come from the address and go
    // to the server, which answers one page with the counts of the whole list.
    let currentPage = initialState.pagina;
    let selectedStatus = STATUSES.includes(initialState.estado) ? initialState.estado : '';
    let requestNumber = 0;
    let searchTimer = null;

    if (searchInput) {
        searchInput.value = initialState.busqueda;
    }

    const pagination = mountPagination(document.querySelector('[data-patients-pagination]'), {
        noun: 'pacientes',
        onChange: function (page) {
            currentPage = page;
            loadPatients({ announcePage: true });
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

    function normalizeStatus(status) {
        switch (status) {
            case 'active': return 'Activo';
            case 'inactive': return 'Inactivo';
            default: return status;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderCounters(counts) {
        if (!counts) {
            return;
        }

        if (countTotal) countTotal.textContent = String(counts.total ?? 0);
        if (countActive) countActive.textContent = String(counts.active ?? 0);
        if (countInactive) countInactive.textContent = String(counts.inactive ?? 0);
    }

    function setActiveStatusButton(value) {
        if (!statusFilter) {
            return;
        }

        statusFilter.querySelectorAll('[data-status-value]').forEach(function (button) {
            var isActive = String(button.getAttribute('data-status-value') || '') === String(value || '');
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function renderPatientCards(records) {
        if (!patientsList) return;

        if (records.length === 0) {
            patientsList.innerHTML = '';
            if (emptyBox) emptyBox.classList.remove('hidden');
            return;
        }

        if (emptyBox) emptyBox.classList.add('hidden');

        patientsList.innerHTML = records.map(function (patient) {
            const id = patient.id ?? '';
            const firstName = patient.first_name ?? '';
            const lastName = patient.last_name ?? '';
            const fullName = `${firstName} ${lastName}`.trim();
            const email = patient.email ?? 'Sin correo';
            const status = patient.status ?? '';
            const statusLabel = normalizeStatus(status);
            
            const statusClasses = status === 'active'
                ? 'bg-success-soft text-success'
                : 'bg-danger-soft text-danger';

            const initial = (firstName.charAt(0) || lastName.charAt(0) || 'P').toUpperCase();

            // "Granito de arena": Colores dinámicos para las iniciales basados en el nombre
            const colors = ['bg-primary-soft text-ink', 'bg-info-soft text-info', 'bg-success-soft text-success', 'bg-warning-soft text-warning'];
            const colorIndex = (firstName.length + lastName.length) % colors.length;
            const avatarClasses = colors[colorIndex];

            return [
                '<article class="rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex items-center gap-3">',
                            '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-box ' + avatarClasses + ' text-sm font-bold" aria-hidden="true">', escapeHtml(initial), '</div>',
                            '<div>',
                                '<h3 class="text-base font-semibold text-ink wrap-break-word">', escapeHtml(fullName), '</h3>',
                                '<span class="inline-flex rounded-full px-2.5 py-0.5 text-min font-bold ' + statusClasses + '">', escapeHtml(statusLabel), '</span>',
                            '</div>',
                        '</div>',
                        '<div class="flex gap-2">',
                            '<button type="button" data-patient-edit data-patient-id="', escapeHtml(id), '" data-patient-first-name="', escapeHtml(firstName), '" data-patient-last-name="', escapeHtml(lastName), '" data-patient-email="', escapeHtml(email), '" data-patient-status="', escapeHtml(status), '" class="flex size-control items-center justify-center rounded-control border border-field bg-surface text-ink transition-colors hover:bg-canvas" aria-label="Editar a ', escapeHtml(fullName), '">',
                                '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
                            '</button>',
                            '<button type="button" data-patient-delete data-patient-id="', escapeHtml(id), '" data-patient-label="', escapeHtml(fullName), '" class="flex size-control items-center justify-center rounded-control border border-field bg-surface text-ink transition-colors hover:bg-danger-soft hover:text-danger" aria-label="Eliminar a ', escapeHtml(fullName), '">',
                                '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
                            '</button>',
                        '</div>',
                    '</div>',
                    
                    '<div class="mt-4">',
                        '<div class="flex items-center gap-2 rounded-box bg-canvas px-4 py-3 text-sm text-muted">',
                            '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
                            '<span class="break-all font-medium">', escapeHtml(email), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex items-center justify-between">',
                         '<a href="/expedientes-clinicos/' + escapeHtml(id) + '" class="inline-flex min-h-control items-center gap-1 text-sm font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4">',
                            'Ver expediente',
                            '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>',
                         '</a>',
                    '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    function searchText() {
        return searchInput ? searchInput.value.trim() : '';
    }

    async function loadPatients(options = {}) {
        const thisRequest = ++requestNumber;

        hideError();
        setLoading(true);

        try {
            const params = new URLSearchParams();

            params.set('page', String(currentPage));

            if (selectedStatus) {
                params.set('status', selectedStatus);
            }

            if (searchText()) {
                params.set('search', searchText());
            }

            const response = await fetch('/api/v1/patients?' + params.toString(), {
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
                const message = 'No se pudieron cargar los pacientes. Inténtalo de nuevo en un momento.';
                showError(message);
                renderPatientCards([]);
                pagination.render(null);
                return;
            }

            const meta = payload?.meta ?? null;

            currentPage = meta?.page ?? 1;
            renderCounters(meta?.counts);
            renderPatientCards(Array.isArray(payload?.data) ? payload.data : []);
            pagination.render(meta);
            writeListState({ pagina: currentPage, busqueda: searchText(), estado: selectedStatus });

            if (options.announcePage) {
                pagination.announcePage(meta);
            }
        } catch (error) {
            if (thisRequest !== requestNumber) {
                return;
            }

            showError('No hay conexión. Revisa tu red e inténtalo de nuevo.');
            renderPatientCards([]);
            pagination.render(null);
        } finally {
            if (thisRequest === requestNumber) {
                setLoading(false);
            }
        }
    }

    function handleStatusFilter(event) {
        var button = event.target.closest('[data-status-value]');

        if (!button || !statusFilter || !statusFilter.contains(button)) {
            return;
        }

        selectedStatus = String(button.getAttribute('data-status-value') || '');
        setActiveStatusButton(selectedStatus);
        currentPage = 1;
        loadPatients();
    }

    function handleSearch() {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            currentPage = 1;
            loadPatients();
        }, SEARCH_DELAY_MS);
    }

    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    if (statusFilter) {
        statusFilter.addEventListener('click', handleStatusFilter);
        setActiveStatusButton(selectedStatus);
    }

    window.patientsPage = {
        reload: function () {
            return loadPatients();
        },
        showError: showError,
        hideError: hideError,
    };

    document.addEventListener('DOMContentLoaded', function () {
        loadPatients();
    });
})();
