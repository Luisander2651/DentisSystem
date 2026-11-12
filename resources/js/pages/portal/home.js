import { stateBadge } from '../../ui/badge';
import { announceError } from '../../ui/status';
import { createAppointmentQr, qrFilename } from '../../ui/appointment-qr';

/*
 * Inicio del paciente (spec 025, c3). Pide su próxima cita y la pinta en la tarjeta del inicio con
 * su QR debajo (CA1); sin cita, el estado vacío con el enlace a contacto (CA2); si falla la cita,
 * el bloque de error con "Reintentar" en su lugar, y si falla el QR, su aviso en el lugar del
 * código (CA9). Todo dato entra con textContent (TM7). Los accesos esperan ocultos a que se sepa
 * qué mostrar, para que nada a la vista salte al llegar la cita (K6).
 */
(function () {
    const root = document.querySelector('[data-portal-home]');

    if (!root || root.dataset.portalReady) {
        return;
    }

    root.dataset.portalReady = 'true';

    const LOAD_ERROR = 'No se pudo cargar esta sección.';
    const WEEKDAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    const section = root.querySelector('[data-portal-section="next"]');
    const card = section.querySelector('[data-next-card]');
    const detail = section.querySelector('[data-portal-next-detail]');
    const qr = createAppointmentQr(section.querySelector('[data-view-appointment-qr]'));    let ticket = 0;

    /** "2026-11-12" → "Jueves 12 de noviembre", read as a date of the calendar, without hours. */
    function longDate(value) {
        const [year, month, day] = String(value || '').split('-').map(Number);

        if (!year || !month || !day) {
            return String(value || '');
        }

        return WEEKDAYS[new Date(Date.UTC(year, month - 1, day)).getUTCDay()] + ' ' + day + ' de ' + MONTHS[month - 1];
    }

    function show(state) {
        section.querySelectorAll('[data-dashboard-state]').forEach((node) => {
            node.hidden = node.dataset.dashboardState !== state;
        });
        section.querySelector('[data-dashboard-content]').hidden = state !== 'content';

        root.querySelectorAll('[data-portal-access]').forEach((access) => {
            // c3-vacio: without an appointment there is nothing to change.
            access.hidden = state === 'loading' || (state === 'empty' && access.dataset.portalAccess === 'change');
        });
    }

    function text(selector, value) {
        const node = card.querySelector(selector);

        if (node) {
            node.textContent = value ?? '';
        }
    }

    function paint(appointment) {
        const date = longDate(appointment.date);

        text('[data-next-time]', appointment.time);
        text('[data-next-patient]', date);
        text('[data-next-detail]', [appointment.treatment_name, appointment.doctor_name].filter(Boolean).join(' · '));

        const holder = document.createElement('template');
        holder.innerHTML = stateBadge(appointment.status);
        card.querySelector('[data-next-status]').replaceChildren(holder.content.firstElementChild);

        detail.dataset.appointmentId = appointment.id;
        let suffix = detail.querySelector('[data-action-for]');

        if (!suffix) {
            suffix = document.createElement('span');
            suffix.className = 'sr-only';
            suffix.dataset.actionFor = '';
            detail.appendChild(suffix);
        }

        suffix.textContent = ' de tu cita del ' + date.toLowerCase();

        show('content');

        if (qr) {
            qr.show({
                src: '/api/v1/portal/appointments/' + encodeURIComponent(appointment.id) + '/qr',
                alt: 'Código QR de acceso de tu cita del ' + date.toLowerCase(),
                filename: qrFilename(appointment.date, appointment.time),
            });
        }
    }

    async function load() {
        const mine = ++ticket;

        if (qr) {
            qr.reset();
        }

        try {
            const response = await fetch('/api/v1/portal/appointments/next', {
                credentials: 'include',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const payload = await response.json();

            if (mine !== ticket) {
                return;
            }

            if (payload.data) {
                paint(payload.data);
            } else {
                show('empty');
            }
        } catch (failure) {
            if (mine !== ticket) {
                return;
            }

            show('error');
            announceError(LOAD_ERROR);
        }
    }

    section.addEventListener('click', (event) => {
        if (!event.target.closest('[data-dashboard-retry]')) {
            return;
        }

        // As on the start of the staff (R7): the focus waits on the title while the section loads
        // and goes to the new "Reintentar" if it fails again.
        const title = section.querySelector('h2');
        title.setAttribute('tabindex', '-1');
        title.focus();
        show('loading');

        load().then(() => {
            const again = section.querySelector('[data-dashboard-state="error"]:not([hidden]) [data-dashboard-retry]');

            if (again && (document.activeElement === title || document.activeElement === document.body)) {
                again.focus();
            }
        });
    });

    load();
})();
