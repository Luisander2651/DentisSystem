import { closeDialog, openDialog } from '../../ui/dialog';
import { stateBadge } from '../../ui/badge';
import { announceError, failureMessage } from '../../ui/status';

(function () {
    if (window.__agendaViewAppointmentInit) {
        return;
    }

    window.__agendaViewAppointmentInit = true;

    var modal = document.querySelector('[data-view-appointment-modal]');

    if (!modal) {
        return;
    }

    var isAdmin = modal.getAttribute('data-view-appointment-is-admin') === 'true';
    var generalContainer = modal.querySelector('[data-view-appointment-general]');
    var clinicalSection = modal.querySelector('[data-view-appointment-clinical-section]');
    var clinicalBody = modal.querySelector('[data-view-appointment-clinical-body]');
    var errorBox = modal.querySelector('[data-view-appointment-error]') || document.createElement('p');
    var editButton = modal.querySelector('[data-view-appointment-edit]');
    var cancelButtons = modal.querySelectorAll('[data-view-appointment-cancel]');

    if (!generalContainer || !clinicalSection || !clinicalBody) {
        return;
    }

    var activeAppointmentId = null;


    function normalizeAppointmentStatus(status) {
        var normalized = String(status || '').trim().toLowerCase();
        var mapping = {
            pending: 'asignada',
            confirmed: 'asignada',
            assigned: 'asignada',
            completed: 'completada',
            cancelled: 'cancelada',
            cancelada: 'cancelada',
            completada: 'completada',
            asignada: 'asignada',
            reprogramada: 'reprogramada',
        };

        return mapping[normalized] || 'asignada';
    }

    function formatTime(time) {
        var parts = String(time || '').split(':');
        if (parts.length < 2) return String(time || '—');

        var hour = parseInt(parts[0], 10);
        var ampm = hour >= 12 ? 'PM' : 'AM';

        return (hour % 12 || 12) + ':' + parts[1] + ' ' + ampm;
    }

    function formatDate(date) {
        if (!date) return '—';

        var parsed = new Date(date + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) return String(date);

        return parsed.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
        announceError(message);
    }

    function hideError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function addRow(container, label, value, html) {
        var row = document.createElement('div');
        row.className = 'border-b border-line py-2';

        var dt = document.createElement('dt');
        dt.className = 'text-min font-bold uppercase tracking-wide text-muted';
        dt.textContent = label;

        var dd = document.createElement('dd');
        dd.className = 'text-sm text-ink wrap-anywhere';

        if (html) {
            dd.innerHTML = value;
        } else {
            dd.textContent = value;
        }

        row.appendChild(dt);
        row.appendChild(dd);
        container.appendChild(row);
    }

    function renderPrescriptions(container, prescriptions) {
        container.innerHTML = '';

        if (!prescriptions.length) {
            var empty = document.createElement('p');
            empty.className = 'text-sm text-muted';
            empty.textContent = 'No se registraron prescripciones para esta cita.';
            container.appendChild(empty);
            return;
        }

        prescriptions.forEach(function (prescription) {
            var card = document.createElement('div');
            card.className = 'rounded-box border border-line bg-canvas p-3 text-sm';

            var title = document.createElement('p');
            title.className = 'font-bold text-ink wrap-anywhere';
            title.textContent = prescription.medication + ' — ' + prescription.dosage;
            card.appendChild(title);

            var meta = document.createElement('p');
            meta.className = 'mt-1 text-sm text-muted';
            meta.textContent = prescription.duration_days + ' días · ' + prescription.daily_frequency + ' veces al día';
            card.appendChild(meta);

            if (prescription.instructions) {
                var instructions = document.createElement('p');
                instructions.className = 'mt-1 text-sm text-ink wrap-anywhere';
                instructions.textContent = prescription.instructions;
                card.appendChild(instructions);
            }

            container.appendChild(card);
        });
    }

    function renderClinicalRecord(record) {
        clinicalBody.innerHTML = '';

        var tracking = record.appointment_tracking || {};
        var prescriptions = record.prescriptions || [];
        var symptoms = Array.isArray(tracking.symptoms) && tracking.symptoms.length ? tracking.symptoms.join(', ') : '—';

        var fieldsWrapper = document.createElement('dl');
        addRow(fieldsWrapper, 'Motivo', tracking.reason || '—');
        addRow(fieldsWrapper, 'Síntomas', symptoms);
        addRow(fieldsWrapper, 'Diagnóstico', tracking.diagnosis || '—');
        addRow(fieldsWrapper, 'Procedimiento', tracking.procedure_performed || '—');
        if (tracking.observations) addRow(fieldsWrapper, 'Observaciones', tracking.observations);
        if (tracking.recommendations) addRow(fieldsWrapper, 'Recomendaciones', tracking.recommendations);
        clinicalBody.appendChild(fieldsWrapper);

        var prescriptionsTitle = document.createElement('h3');
        prescriptionsTitle.className = 'mt-4 text-sm font-bold text-ink';
        prescriptionsTitle.textContent = 'Prescripciones';
        clinicalBody.appendChild(prescriptionsTitle);

        var prescriptionsContainer = document.createElement('div');
        prescriptionsContainer.className = 'mt-2 space-y-3';
        clinicalBody.appendChild(prescriptionsContainer);

        renderPrescriptions(prescriptionsContainer, prescriptions);
    }

    async function requestJson(url) {
        var response = await fetch(url, {
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(failureMessage(response.status, data));
        }

        return data;
    }

    async function open(appointmentId, opener) {
        activeAppointmentId = appointmentId;
        hideError();
        generalContainer.innerHTML = '';
        clinicalSection.classList.add('hidden');
        clinicalBody.innerHTML = '';
        if (editButton) editButton.hidden = true;

        openDialog(modal, opener || document.activeElement);

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};
            var normalizedStatus = normalizeAppointmentStatus(appointment.status);

            addRow(generalContainer, 'Paciente', appointment.patient_name || 'Paciente');
            addRow(generalContainer, 'Tratamiento', appointment.treatment_name || 'Consulta');
            addRow(generalContainer, 'Doctor', appointment.user_name || '—');
            addRow(generalContainer, 'Fecha', formatDate(appointment.date));
            addRow(generalContainer, 'Hora', formatTime(appointment.time));
            addRow(generalContainer, 'Estado', stateBadge(normalizedStatus), true);

            var canEdit = window.agendaAppointmentModal && typeof window.agendaAppointmentModal.openEdit === 'function';
            if ((normalizedStatus === 'asignada' || normalizedStatus === 'reprogramada') && editButton && canEdit) {
                editButton.hidden = false;
            }

            if (normalizedStatus === 'completada' && isAdmin) {
                clinicalSection.classList.remove('hidden');

                var loading = document.createElement('p');
                loading.className = 'text-sm text-muted';
                loading.textContent = 'Cargando registro clínico...';
                clinicalBody.appendChild(loading);

                try {
                    var trackingData = await requestJson('/api/v1/appointments/' + appointmentId + '/tracking');
                    renderClinicalRecord(trackingData.data || {});
                } catch (trackingError) {
                    clinicalBody.innerHTML = '';
                    var trackingErrorEl = document.createElement('p');
                    trackingErrorEl.className = 'rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger';
                    trackingErrorEl.setAttribute('role', 'alert');
                    trackingErrorEl.textContent = trackingError instanceof TypeError ? failureMessage(0) : trackingError.message;
                    clinicalBody.appendChild(trackingErrorEl);
                }
            }
        } catch (error) {
            showError(error instanceof TypeError ? failureMessage(0) : error.message);
        }
    }

    function closeModal() {
        closeDialog(modal);
        activeAppointmentId = null;
    }

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    if (editButton) {
        editButton.addEventListener('click', function () {
            var appointmentId = activeAppointmentId;
            closeModal();
            if (window.agendaAppointmentModal && typeof window.agendaAppointmentModal.openEdit === 'function' && appointmentId) {
                window.agendaAppointmentModal.openEdit(appointmentId);
            }
        });
    }

    window.agendaViewAppointmentModal = {
        open: open,
    };
})();
