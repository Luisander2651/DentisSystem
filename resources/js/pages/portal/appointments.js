import { renderAppointmentsHistory } from '../../ui/appointments-history';
import { announceError } from '../../ui/status';

/*
 * Mis citas (spec 025, c3). Pide el historial del paciente y lo pinta con la tabla del expediente
 * (tarjetas bajo 768 px, tabla desde 768 px), de la más reciente a la más antigua (CA3), con las
 * fechas en dd/mm/aaaa y la hora en HH:MM; sin citas, "Aún no tienes citas registradas" (CA4); si
 * falla, el bloque de error con "Reintentar" en su lugar (CA9). Todo dato va escapado.
 */
(function () {
    const root = document.querySelector('[data-portal-appointments]');

    if (!root || root.dataset.portalReady) {
        return;
    }

    root.dataset.portalReady = 'true';

    const LOAD_ERROR = 'No se pudo cargar esta sección.';
    const status = root.querySelector('[data-portal-history-status]');
    const content = root.querySelector('[data-dashboard-content]');
    let ticket = 0;

    /** "2026-11-26" → "26/11/2026". */
    function shortDate(value) {
        const [year, month, day] = String(value || '').split('-');

        return year && month && day ? day + '/' + month + '/' + year : String(value || '');
    }

    function show(state) {
        status.hidden = state === 'content';
        status.querySelectorAll('[data-dashboard-state]').forEach((node) => {
            node.hidden = node.dataset.dashboardState !== state;
        });
        content.hidden = state !== 'content';
    }

    function paint(appointments) {
        const list = Array.isArray(appointments) ? appointments : [];

        root.querySelectorAll('[data-record-when="appointments"]').forEach((node) => node.classList.toggle('hidden', list.length === 0));
        root.querySelectorAll('[data-record-when-empty="appointments"]').forEach((node) => node.classList.toggle('hidden', list.length > 0));

        renderAppointmentsHistory({
            tbody: root.querySelector('#record-appointments-body'),
            cards: root.querySelector('#record-appointments-list'),
            appointments: list,
            emptyText: 'Aún no tienes citas registradas',
            formatDate: shortDate,
            formatTime: (time) => String(time || '').slice(0, 5),
            viewAttribute: 'data-portal-view-appointment',
        });

        show('content');
    }

    async function load() {
        const mine = ++ticket;

        try {
            const response = await fetch('/api/v1/portal/appointments', {
                credentials: 'include',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const payload = await response.json();

            if (mine === ticket) {
                paint(payload.data);
            }
        } catch (failure) {
            if (mine === ticket) {
                show('error');
                announceError(LOAD_ERROR);
            }
        }
    }

    status.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dashboard-retry]')) {
            return;
        }

        // As on the start (R7): the focus waits on the title while the list loads again.
        const title = root.querySelector('h1');
        title.setAttribute('tabindex', '-1');
        title.focus();
        show('loading');
        load();
    });

    load();
})();
