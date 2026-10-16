import { closeDialog, openDialog } from '../../ui/dialog';
import { announce, announceError, failureMessage } from '../../ui/status';

/*
 * Cancelar una cita (spec 017, CA7): la confirmación envía solo `PUT {status: "cancelada"}`. Con
 * éxito cierra la confirmación y el detalle, avisa "Cita cancelada." y recarga la agenda; si falla,
 * la confirmación sigue abierta con el error en su región.
 */
(function () {
    if (window.__agendaCancelAppointmentInit) {
        return;
    }

    window.__agendaCancelAppointmentInit = true;

    var modal = document.querySelector('[data-cancel-appointment-confirm]');

    if (!modal) {
        return;
    }

    var patientTarget = modal.querySelector('[data-cancel-appointment-patient]');
    var dateTarget = modal.querySelector('[data-cancel-appointment-date]');
    var timeTarget = modal.querySelector('[data-cancel-appointment-time]');
    var keepButton = modal.querySelector('[data-cancel-appointment-keep]');
    var acceptButton = modal.querySelector('[data-cancel-appointment-accept]');

    var activeAppointmentId = null;
    var isCancelling = false;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    /**
     * @param {{id: string, patient: string, date: string, time: string}} appointment
     * @param {HTMLElement} [opener] el control al que vuelve el foco si no se cancela
     */
    function open(appointment, opener) {
        activeAppointmentId = appointment.id;

        if (patientTarget) patientTarget.textContent = appointment.patient || 'este paciente';
        if (dateTarget) dateTarget.textContent = appointment.date || '';
        if (timeTarget) timeTarget.textContent = appointment.time || '';

        openDialog(modal, opener || document.activeElement);
    }

    function close() {
        if (isCancelling) {
            return;
        }

        closeDialog(modal);
    }

    async function cancelAppointment(appointmentId) {
        var response = await fetch('/api/v1/appointments/' + encodeURIComponent(appointmentId), {
            method: 'PUT',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') || '',
            },
            body: JSON.stringify({ status: 'cancelada' }),
        });

        var payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(failureMessage(response.status, payload));
        }
    }

    modal.addEventListener('close', function () {
        activeAppointmentId = null;
    });

    if (keepButton) {
        keepButton.addEventListener('click', close);
    }

    if (acceptButton) {
        acceptButton.addEventListener('click', async function () {
            if (!activeAppointmentId || isCancelling) {
                return;
            }

            isCancelling = true;
            acceptButton.disabled = true;

            try {
                await cancelAppointment(activeAppointmentId);
            } catch (error) {
                announceError(error instanceof TypeError ? failureMessage(0) : error.message);

                return;
            } finally {
                isCancelling = false;
                acceptButton.disabled = false;
            }

            closeDialog(modal);
            closeDialog(document.querySelector('[data-view-appointment-modal]'));
            announce('Cita cancelada.');

            if (window.agendaPage && typeof window.agendaPage.reload === 'function') {
                window.agendaPage.reload().catch(function (reloadError) {
                    console.error('No se pudo recargar la agenda tras cancelar la cita:', reloadError);
                });
            }
        });
    }

    window.agendaCancelAppointment = {
        open: open,
        close: close,
    };
})();
