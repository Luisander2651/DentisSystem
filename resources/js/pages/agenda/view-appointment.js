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
    var errorBox = modal.querySelector('[data-view-appointment-error]');
    var editButton = modal.querySelector('[data-view-appointment-edit]');
    var cancelButtons = modal.querySelectorAll('[data-view-appointment-cancel]');

    if (!generalContainer || !clinicalSection || !clinicalBody) {
        return;
    }

    var activeAppointmentId = null;

    var statusLabel = {
        asignada: 'Asignada',
        completada: 'Completada',
        cancelada: 'Cancelada',
        reprogramada: 'Reprogramada',
    };

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
    }

    function hideError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function addRow(container, label, value) {
        var row = document.createElement('div');
        row.className = 'flex items-start justify-between gap-4 py-3 text-sm';

        var dt = document.createElement('span');
        dt.className = 'font-bold text-slate-500';
        dt.textContent = label;

        var dd = document.createElement('span');
        dd.className = 'text-right font-medium text-slate-900';
        dd.textContent = value;

        row.appendChild(dt);
        row.appendChild(dd);
        container.appendChild(row);
    }

    function renderPrescriptions(container, prescriptions) {
        container.innerHTML = '';

        if (!prescriptions.length) {
            var empty = document.createElement('p');
            empty.className = 'rounded-2xl border border-dashed border-slate-200 py-6 text-center text-xs font-semibold text-slate-400';
            empty.textContent = 'No se registraron prescripciones para esta cita.';
            container.appendChild(empty);
            return;
        }

        prescriptions.forEach(function (prescription) {
            var card = document.createElement('div');
            card.className = 'rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm';

            var title = document.createElement('p');
            title.className = 'font-bold text-slate-900';
            title.textContent = prescription.medication + ' — ' + prescription.dosage;
            card.appendChild(title);

            var meta = document.createElement('p');
            meta.className = 'mt-1 text-xs font-semibold text-slate-500';
            meta.textContent = prescription.duration_days + ' días · ' + prescription.daily_frequency + ' veces al día';
            card.appendChild(meta);

            if (prescription.instructions) {
                var instructions = document.createElement('p');
                instructions.className = 'mt-2 text-xs text-slate-600';
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

        var fieldsWrapper = document.createElement('div');
        fieldsWrapper.className = 'divide-y divide-slate-50 rounded-2xl border border-slate-200 bg-white px-4';
        addRow(fieldsWrapper, 'Motivo', tracking.reason || '—');
        addRow(fieldsWrapper, 'Síntomas', symptoms);
        addRow(fieldsWrapper, 'Diagnóstico', tracking.diagnosis || '—');
        addRow(fieldsWrapper, 'Procedimiento', tracking.procedure_performed || '—');
        if (tracking.observations) addRow(fieldsWrapper, 'Observaciones', tracking.observations);
        if (tracking.recommendations) addRow(fieldsWrapper, 'Recomendaciones', tracking.recommendations);
        clinicalBody.appendChild(fieldsWrapper);

        var prescriptionsTitle = document.createElement('p');
        prescriptionsTitle.className = 'mt-4 text-xs font-bold uppercase tracking-wider text-slate-400';
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
            throw new Error(data.error || data.message || 'No se pudo completar la solicitud.');
        }

        return data;
    }

    async function open(appointmentId) {
        activeAppointmentId = appointmentId;
        hideError();
        generalContainer.innerHTML = '';
        clinicalSection.classList.add('hidden');
        clinicalBody.innerHTML = '';
        if (editButton) editButton.classList.add('hidden');

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};
            var normalizedStatus = normalizeAppointmentStatus(appointment.status);

            addRow(generalContainer, 'Paciente', appointment.patient_name || 'Paciente');
            addRow(generalContainer, 'Tratamiento', appointment.treatment_name || 'Consulta');
            addRow(generalContainer, 'Doctor', appointment.user_name || '—');
            addRow(generalContainer, 'Fecha', formatDate(appointment.date));
            addRow(generalContainer, 'Hora', formatTime(appointment.time));
            addRow(generalContainer, 'Estado', statusLabel[normalizedStatus] || appointment.status);

            var canEdit = window.agendaAppointmentModal && typeof window.agendaAppointmentModal.openEdit === 'function';
            if ((normalizedStatus === 'asignada' || normalizedStatus === 'reprogramada') && editButton && canEdit) {
                editButton.classList.remove('hidden');
            }

            if (normalizedStatus === 'completada' && isAdmin) {
                clinicalSection.classList.remove('hidden');

                var loading = document.createElement('p');
                loading.className = 'text-xs font-semibold text-slate-400';
                loading.textContent = 'Cargando registro clínico...';
                clinicalBody.appendChild(loading);

                try {
                    var trackingData = await requestJson('/api/v1/appointments/' + appointmentId + '/tracking');
                    renderClinicalRecord(trackingData.data || {});
                } catch (trackingError) {
                    clinicalBody.innerHTML = '';
                    var trackingErrorEl = document.createElement('p');
                    trackingErrorEl.className = 'rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-xs font-bold text-red-700';
                    trackingErrorEl.textContent = trackingError.message || 'No se pudo cargar el registro clínico.';
                    clinicalBody.appendChild(trackingErrorEl);
                }
            }
        } catch (error) {
            showError(error.message || 'No se pudo cargar la información de la cita.');
        }
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
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
