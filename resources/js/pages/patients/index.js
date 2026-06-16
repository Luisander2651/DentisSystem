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

    const countTotal = document.querySelector('[data-patients-total-count]');
    const countActive = document.querySelector('[data-patients-active-count]');
    const countInactive = document.querySelector('[data-patients-inactive-count]');

    let allPatients = [];

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

    function renderPatientCards(records) {
        if (!patientsList) return;

        let activeCount = 0;
        let inactiveCount = 0;

        allPatients.forEach(p => {
            if (p.status === 'active') activeCount++;
            else if (p.status === 'inactive') inactiveCount++;
        });

        if (countTotal) countTotal.textContent = String(allPatients.length);
        if (countActive) countActive.textContent = String(activeCount);
        if (countInactive) countInactive.textContent = String(inactiveCount);

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
                                '<h3 class="text-base font-semibold text-slate-900 break-words group-hover:text-[#B5114A] transition-colors">', escapeHtml(fullName), '</h3>',
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
            const response = await fetch('/api/v1/patients', {
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

            allPatients = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            renderPatientCards(allPatients);
        } catch (error) {
            showError('Error de conexion. Intentalo de nuevo.');
            renderPatientCards([]);
        } finally {
            setLoading(false);
        }
    }

    function handleSearch() {
        const query = searchInput.value.toLowerCase().trim();
        if (!query) {
            renderPatientCards(allPatients);
            return;
        }

        const filtered = allPatients.filter(p => {
            const fullName = `${p.first_name || ''} ${p.last_name || ''}`.toLowerCase();
            const email = (p.email || '').toLowerCase();
            return fullName.includes(query) || email.includes(query);
        });

        renderPatientCards(filtered);
    }

    if (searchInput) {
        searchInput.addEventListener('input', handleSearch);
    }

    window.patientsPage = {
        reload: loadPatients,
        showError: showError,
        hideError: hideError,
    };

    document.addEventListener('DOMContentLoaded', loadPatients);
})();
