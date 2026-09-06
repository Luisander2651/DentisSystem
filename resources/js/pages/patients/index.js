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

    let allPatients = [];
    let patientCounters = {
        total: 0,
        active: 0,
        inactive: 0,
    };
    let selectedStatus = '';

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

    function normalizePatientRecord(patient) {
        const firstName = patient.first_name ?? '';
        const lastName = patient.last_name ?? '';
        const email = patient.email ?? '';

        return Object.assign({}, patient, {
            _searchText: `${firstName} ${lastName} ${email}`.toLowerCase(),
        });
    }

    function rebuildCounters(records) {
        let activeCount = 0;
        let inactiveCount = 0;

        records.forEach(function (patient) {
            if (patient.status === 'active') {
                activeCount += 1;
            } else if (patient.status === 'inactive') {
                inactiveCount += 1;
            }
        });

        patientCounters = {
            total: records.length,
            active: activeCount,
            inactive: inactiveCount,
        };
    }

    function renderCounters() {
        if (countTotal) countTotal.textContent = String(patientCounters.total);
        if (countActive) countActive.textContent = String(patientCounters.active);
        if (countInactive) countInactive.textContent = String(patientCounters.inactive);
    }

    function setActiveStatusButton(value) {
        if (!statusFilter) {
            return;
        }

        statusFilter.querySelectorAll('[data-status-value]').forEach(function (button) {
            var isActive = String(button.getAttribute('data-status-value') || '') === String(value || '');
            button.classList.toggle('bg-[#FDF1F6]', isActive);
            button.classList.toggle('text-[#B5114A]', isActive);
            button.classList.toggle('text-slate-600', !isActive);
        });
    }

    function renderPatientCards(records) {
        if (!patientsList) return;

        renderCounters();

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
                ? 'bg-emerald-50 text-emerald-700'
                : 'bg-slate-100 text-slate-600';

            const initial = (firstName.charAt(0) || lastName.charAt(0) || 'P').toUpperCase();

            // "Granito de arena": Colores dinámicos para las iniciales basados en el nombre
            const colors = ['bg-[#FDF1F6] text-[#B5114A]', 'bg-sky-50 text-sky-700', 'bg-emerald-50 text-emerald-700', 'bg-amber-50 text-amber-700'];
            const colorIndex = (firstName.length + lastName.length) % colors.length;
            const avatarClasses = colors[colorIndex];

            return [
                '<article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-[#F5C2D6] hover:shadow-md">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex items-center gap-3">',
                            '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ' + avatarClasses + ' text-sm font-bold transition-transform group-hover:scale-110">', escapeHtml(initial), '</div>',
                            '<div>',
                                '<h3 class="text-base font-semibold text-slate-900 wrap-break-word group-hover:text-[#B5114A] transition-colors">', escapeHtml(fullName), '</h3>',
                                '<span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider ' + statusClasses + '">', escapeHtml(statusLabel), '</span>',
                            '</div>',
                        '</div>',
                        '<div class="flex gap-1">',
                            '<button type="button" data-patient-edit data-patient-id="', escapeHtml(id), '" data-patient-first-name="', escapeHtml(firstName), '" data-patient-last-name="', escapeHtml(lastName), '" data-patient-email="', escapeHtml(email), '" data-patient-status="', escapeHtml(status), '" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition" title="Editar">',
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>',
                            '</button>',
                            '<button type="button" data-patient-delete data-patient-id="', escapeHtml(id), '" data-patient-label="', escapeHtml(fullName), '" class="flex h-8 w-8 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600 transition" title="Eliminar">',
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>',
                            '</button>',
                        '</div>',
                    '</div>',
                    
                    '<div class="mt-4">',
                        '<div class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-xs text-slate-600 group-hover:bg-[#FFF7FA] transition-colors">',
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 group-hover:text-[#B5114A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
                            '<span class="break-all font-medium">', escapeHtml(email), '</span>',
                        '</div>',
                    '</div>',

                    '<div class="mt-4 flex items-center justify-between">',
                         '<a href="/expedientes-clinicos/' + escapeHtml(id) + '" class="text-xs font-bold text-[#B5114A] hover:underline flex items-center gap-1">',
                            'Ver Expediente',
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>',
                         '</a>',
                    '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    async function loadPatients() {
        hideError();
        setLoading(true);

        try {
            const params = new URLSearchParams();

            if (selectedStatus) {
                params.set('status', selectedStatus);
            }

            const response = await fetch('/api/v1/patients' + (params.toString() ? '?' + params.toString() : ''), {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.error || payload.message || 'No se pudieron cargar los pacientes.';
                showError(message);
                renderPatientCards([]);
                return;
            }

            allPatients = (Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []))
                .map(normalizePatientRecord);

            rebuildCounters(allPatients);
            renderPatientCards(allPatients);
        } catch (error) {
            showError('Error de conexion. Intentalo de nuevo.');
            renderPatientCards([]);
        } finally {
            setLoading(false);
        }
    }

    function handleStatusFilter(event) {
        var button = event.target.closest('[data-status-value]');

        if (!button || !statusFilter || !statusFilter.contains(button)) {
            return;
        }

        selectedStatus = String(button.getAttribute('data-status-value') || '');
        setActiveStatusButton(selectedStatus);
        loadPatients();
    }

    function handleSearch() {
        const query = searchInput.value.toLowerCase().trim();
        if (!query) {
            renderPatientCards(allPatients);
            return;
        }

        const filtered = allPatients.filter(p => {
            return String(p._searchText || '').includes(query);
        });

        renderPatientCards(filtered);
    }

    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    if (statusFilter) {
        statusFilter.addEventListener('click', handleStatusFilter);
        setActiveStatusButton(selectedStatus);
    }

    window.patientsPage = {
        reload: loadPatients,
        showError: showError,
        hideError: hideError,
    };

    document.addEventListener('DOMContentLoaded', loadPatients);
})();
