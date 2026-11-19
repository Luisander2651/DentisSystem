import { renderAppointmentsHistory } from '../../ui/appointments-history';
import { mountPagination, readListState, writeListState } from '../../ui/pagination';
import { openAppointmentDetail } from './appointment-detail';
import { announceError } from '../../ui/status';

/*
 * Mis citas (spec 025, c3). Pide el historial del paciente y lo pinta con la tabla del expediente
 * (tarjetas bajo 768 px, tabla desde 768 px), de la más reciente a la más antigua (CA3), con las
 * fechas en dd/mm/aaaa y la hora en HH:MM; sin citas, "Aún no tienes citas registradas" (CA4); si
 * falla, el bloque de error con "Reintentar" en su lugar (CA9). Todo dato va escapado.
 *
 * Spec 028 (CA2, CA8): diez citas por página, con la página en la dirección; "Ver" abre el detalle
 * con la cita de la página cargada (D15).
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
    let currentPage = readListState(window.location.search).pagina;
    const shown = new Map();
    const historyNav = root.querySelector('[data-history-pagination]');
    const pagination = mountPagination(historyNav, {
        noun: 'citas',
        onChange: (page) => {
            currentPage = page;
            load({ announcePage: true });
        },
        focusTarget: historyNav ? historyNav.closest('section').querySelector('h2') : null,
    });

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

    function paint(appointments, meta) {
        const list = Array.isArray(appointments) ? appointments : [];

        shown.clear();
        list.forEach((appointment) => shown.set(String(appointment.id), appointment));

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

        pagination.render(meta);
        show('content');
    }

    async function load(options = {}) {
        const mine = ++ticket;

        try {
            const response = await fetch('/api/v1/portal/appointments?page=' + encodeURIComponent(String(currentPage)), {
                credentials: 'include',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const payload = await response.json();

            if (mine === ticket) {
                const meta = payload.meta ?? null;

                currentPage = meta?.page ?? 1;
                paint(payload.data, meta);
                writeListState({ pagina: currentPage, busqueda: '', estado: '' });

                if (options.announcePage) {
                    pagination.announcePage(meta);
                }
            }
        } catch (failure) {
            if (mine === ticket) {
                show('error');
                announceError(LOAD_ERROR);
            }
        }
    }

    root.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-portal-view-appointment]');

        if (opener) {
            openAppointmentDetail(shown.get(String(opener.dataset.appointmentId)) ?? null, opener);
        }
    });

    status.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dashboard-retry]')) {
            return;
        }

        // As on the start (R7): the focus waits on the title while the list loads again and goes
        // to the new "Reintentar" if it fails again.
        const title = root.querySelector('h1');
        title.setAttribute('tabindex', '-1');
        title.focus();
        show('loading');

        load().then(() => {
            const again = status.querySelector('[data-dashboard-state="error"]:not([hidden]) [data-dashboard-retry]');

            if (again && (document.activeElement === title || document.activeElement === document.body)) {
                again.focus();
            }
        });
    });

    load();
})();
