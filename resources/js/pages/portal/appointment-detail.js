import { openDialog } from '../../ui/dialog';
import { stateBadge } from '../../ui/badge';
import { announceError } from '../../ui/status';
import { createAppointmentQr, qrFilename } from '../../ui/appointment-qr';
import { renderPrescriptions } from '../../ui/prescriptions';

/*
 * Detalle de una cita del paciente (spec 025, c3-detalle). Se abre desde "Ver detalle" del inicio y
 * desde "Ver" de Mis citas, que llevan el id de la cita; la cita sale de la lista del paciente (la
 * API del portal no da una sola). En una cita abierta muestra su QR con "Descargar QR" (CA7); en una
 * completada, las recomendaciones y las recetas o "Esta cita no tiene recetas" (CA5, CA6); en una
 * cancelada, ninguna de las dos (CA8). Si falla el QR o las recetas, su aviso va en su lugar y el
 * resto se queda igual (CA9). Todo dato entra con textContent (TM7).
 */
(function () {
    const modal = document.querySelector('[data-portal-detail-modal]');

    if (!modal || modal.dataset.portalReady) {
        return;
    }

    modal.dataset.portalReady = 'true';

    const OPEN = ['asignada', 'reprogramada'];
    const loading = modal.querySelector('[data-portal-detail-loading]');
    const failure = modal.querySelector('[data-portal-detail-error]');
    const general = modal.querySelector('[data-portal-detail-general]');
    const recommendations = modal.querySelector('[data-portal-detail-recommendations]');
    const recommendationsText = modal.querySelector('[data-portal-detail-recommendations-text]');
    const prescriptions = modal.querySelector('[data-portal-detail-prescriptions]');
    const prescriptionsList = modal.querySelector('[data-portal-detail-prescriptions-list]');
    const prescriptionsError = modal.querySelector('[data-portal-detail-prescriptions-error]');
    const qr = createAppointmentQr(modal.querySelector('[data-view-appointment-qr]'));
    const WEEKDAYS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    // Each opening gets its number and closing moves it on, so a late answer never fills another.
    let opening = 0;

    function parts(date) {
        const [year, month, day] = String(date || '').split('-').map(Number);

        return year && month && day ? { year, month, day } : null;
    }

    /** "2026-11-12" → "12/11/2026". */
    function shortDate(date) {
        const value = parts(date);

        return value ? String(value.day).padStart(2, '0') + '/' + String(value.month).padStart(2, '0') + '/' + value.year : String(date || '');
    }

    /** "2026-11-12" → "jueves 12 de noviembre". */
    function longDate(date) {
        const value = parts(date);

        return value ? WEEKDAYS[new Date(Date.UTC(value.year, value.month - 1, value.day)).getUTCDay()] + ' ' + value.day + ' de ' + MONTHS[value.month - 1] : String(date || '');
    }

    function addRow(label, value) {
        const row = document.createElement('div');
        row.className = 'border-b border-line py-2';

        const term = document.createElement('dt');
        term.className = 'text-min font-bold uppercase tracking-wide text-muted';
        term.textContent = label;

        const detail = document.createElement('dd');
        detail.className = 'text-sm text-ink wrap-anywhere';

        if (value instanceof Node) {
            detail.appendChild(value);
        } else {
            detail.textContent = value || '—';
        }

        row.append(term, detail);
        general.appendChild(row);
    }

    function badge(status) {
        const holder = document.createElement('template');
        holder.innerHTML = stateBadge(status);

        return holder.content.firstElementChild;
    }

    function reset() {
        loading.hidden = false;
        failure.classList.add('hidden');
        general.replaceChildren();
        recommendations.classList.add('hidden');
        recommendationsText.textContent = '';
        prescriptions.classList.add('hidden');
        prescriptionsList.replaceChildren();
        prescriptionsError.classList.add('hidden');

        if (qr) {
            qr.reset();
        }
    }

    async function read(url) {
        const response = await fetch(url, {
            credentials: 'include',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        return (await response.json()).data;
    }

    async function showPrescriptions(appointment, mine) {
        prescriptions.classList.remove('hidden');

        try {
            const data = await read('/api/v1/portal/appointments/' + encodeURIComponent(appointment.id) + '/prescriptions');

            if (mine !== opening) {
                return;
            }

            const text = data && typeof data.recommendations === 'string' ? data.recommendations.trim() : '';

            if (text !== '') {
                recommendationsText.textContent = text;
                recommendations.classList.remove('hidden');
            }

            renderPrescriptions(prescriptionsList, Array.isArray(data?.prescriptions) ? data.prescriptions : [], 'Esta cita no tiene recetas');
        } catch (error) {
            if (mine !== opening) {
                return;
            }

            prescriptionsError.classList.remove('hidden');
            announceError(prescriptionsError.textContent.trim());
        }
    }

    async function open(appointmentId, opener) {
        const mine = ++opening;

        reset();
        openDialog(modal, opener);

        let appointment = null;

        try {
            const list = await read('/api/v1/portal/appointments');
            appointment = (Array.isArray(list) ? list : []).find((item) => String(item.id) === String(appointmentId)) ?? null;
        } catch (error) {
            appointment = null;
        }

        if (mine !== opening) {
            return;
        }

        loading.hidden = true;

        if (!appointment) {
            failure.classList.remove('hidden');
            announceError(failure.textContent.trim());

            return;
        }

        addRow('Tratamiento', appointment.treatment_name);
        addRow('Doctor', appointment.doctor_name);
        addRow('Fecha', shortDate(appointment.date));
        addRow('Hora', String(appointment.time || '').slice(0, 5));
        addRow('Estado', badge(appointment.status));

        if (OPEN.includes(appointment.status)) {
            if (qr) {
                qr.show({
                    src: '/api/v1/portal/appointments/' + encodeURIComponent(appointment.id) + '/qr',
                    alt: 'Código QR de acceso de tu cita del ' + longDate(appointment.date),
                    filename: qrFilename(appointment.date, appointment.time),
                });
            }
        } else if (appointment.status === 'completada') {
            showPrescriptions(appointment, mine);
        }
    }

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-portal-next-detail], [data-portal-view-appointment]');

        if (opener && opener.dataset.appointmentId) {
            open(opener.dataset.appointmentId, opener);
        }
    });

    // However the dialog closes (Cerrar, Escape, the X or the backdrop), the opening ends.
    modal.addEventListener('close', () => {
        if (modal.open) {
            return;
        }

        opening++;

        if (qr) {
            qr.reset();
        }
    });
})();
