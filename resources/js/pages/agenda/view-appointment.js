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

    var trackingScope = modal.getAttribute('data-view-appointment-tracking-scope') || 'none';
    var actorId = modal.getAttribute('data-view-appointment-actor-id') || '';
    var generalContainer = modal.querySelector('[data-view-appointment-general]');
    var clinicalSection = modal.querySelector('[data-view-appointment-clinical-section]');
    var clinicalBody = modal.querySelector('[data-view-appointment-clinical-body]');
    var errorBox = modal.querySelector('[data-view-appointment-error]') || document.createElement('p');
    var editButton = modal.querySelector('[data-view-appointment-edit]');
    var cancelAppointmentButton = modal.querySelector('[data-view-appointment-cancel-appointment]');
    var cancelButtons = modal.querySelectorAll('[data-view-appointment-cancel]');
    var qrSection = modal.querySelector('[data-view-appointment-qr]');
    var qrImage = modal.querySelector('[data-view-appointment-qr-image]');
    var qrError = modal.querySelector('[data-view-appointment-qr-error]');
    var qrDownload = modal.querySelector('[data-view-appointment-qr-download]');
    // Spec 024 (D3): the PNG is drawn at this size, on the surface colour, so a phone reads it in a chat.
    var QR_DOWNLOAD_SIZE = 1024;

    if (!generalContainer || !clinicalSection || !clinicalBody) {
        return;
    }

    var activeAppointmentId = null;
    // What the cancel confirmation names: the patient, the date and the time (spec 017, CA7).
    var activeAppointmentSummary = null;


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
            var failure = new Error(failureMessage(response.status, data));
            failure.status = response.status;
            throw failure;
        }

        return data;
    }

    function resetQr() {
        if (!qrSection) return;

        qrSection.classList.add('hidden');
        qrError.classList.add('hidden');
        qrImage.classList.remove('invisible');
        qrImage.onload = null;
        qrImage.onerror = null;
        qrImage.removeAttribute('src');
        qrImage.alt = '';
        qrDownload.disabled = true;
        qrDownload.removeAttribute('data-view-appointment-qr-filename');
    }

    // Spec 024 (CA5, CA8, D2, D4): the image comes straight from the API, so the content
    // security policy stays as it is and the SVG can run nothing. The file name has no patient data.
    function showQr(appointmentId, date, time) {
        if (!qrSection) return;

        try {
            var filename = 'cita-' + String(date || '') + '-' + String(time || '').slice(0, 5).replace(':', '') + '.png';

            qrSection.classList.remove('hidden');
            qrDownload.setAttribute('data-view-appointment-qr-filename', filename);
            qrImage.alt = 'Código QR de la cita del ' + formatDate(date);
            qrImage.onload = function () {
                if (activeAppointmentId === appointmentId) {
                    qrDownload.disabled = false;
                }
            };
            qrImage.onerror = function () {
                if (activeAppointmentId === appointmentId) {
                    qrImage.classList.add('invisible');
                    qrError.classList.remove('hidden');
                }
            };
            qrImage.src = '/api/v1/appointments/' + encodeURIComponent(appointmentId) + '/qr';
        } catch (qrFailure) {
            qrImage.classList.add('invisible');
            qrError.classList.remove('hidden');
        }
    }

    // Spec 024 (CA6, D3): an SVG does not preview in a chat, so the download is a PNG drawn here
    // from the image already shown, on a white background (the SVG has none).
    function downloadQr() {
        var filename = qrDownload.getAttribute('data-view-appointment-qr-filename');

        if (!filename || !qrImage.complete) return;

        try {
            var canvas = document.createElement('canvas');
            canvas.width = QR_DOWNLOAD_SIZE;
            canvas.height = QR_DOWNLOAD_SIZE;
            var context = canvas.getContext('2d');
            context.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--color-surface').trim();
            context.fillRect(0, 0, QR_DOWNLOAD_SIZE, QR_DOWNLOAD_SIZE);
            context.drawImage(qrImage, 0, 0, QR_DOWNLOAD_SIZE, QR_DOWNLOAD_SIZE);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    announceError('No pudimos descargar el código QR. Inténtalo de nuevo.');
                    return;
                }

                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                setTimeout(function () {
                    URL.revokeObjectURL(url);
                }, 0);
            }, 'image/png');
        } catch (downloadFailure) {
            announceError('No pudimos descargar el código QR. Inténtalo de nuevo.');
        }
    }

    async function open(appointmentId, opener) {
        activeAppointmentId = appointmentId;
        activeAppointmentSummary = null;
        hideError();
        resetQr();
        generalContainer.innerHTML = '';
        clinicalSection.classList.add('hidden');
        clinicalBody.innerHTML = '';
        if (editButton) editButton.hidden = true;
        if (cancelAppointmentButton) cancelAppointmentButton.hidden = true;

        openDialog(modal, opener || document.activeElement);

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};
            var normalizedStatus = normalizeAppointmentStatus(appointment.status);

            activeAppointmentSummary = {
                id: appointmentId,
                patient: appointment.patient_name || 'Paciente',
                date: formatDate(appointment.date),
                time: formatTime(appointment.time),
            };

            addRow(generalContainer, 'Paciente', appointment.patient_name || 'Paciente');
            addRow(generalContainer, 'Tratamiento', appointment.treatment_name || 'Consulta');
            addRow(generalContainer, 'Doctor', appointment.user_name || '—');
            addRow(generalContainer, 'Fecha', formatDate(appointment.date));
            addRow(generalContainer, 'Hora', formatTime(appointment.time));
            addRow(generalContainer, 'Estado', stateBadge(normalizedStatus), true);

            // Spec 017 (CA8): only an open appointment can be rescheduled or cancelled, and only
            // where the agenda loaded those dialogs (not in the clinical record).
            var isOpen = normalizedStatus === 'asignada' || normalizedStatus === 'reprogramada';
            var canEdit = window.agendaAppointmentModal && typeof window.agendaAppointmentModal.openEdit === 'function';
            var canCancel = window.agendaCancelAppointment && typeof window.agendaCancelAppointment.open === 'function';
            if (isOpen && editButton && canEdit) {
                editButton.hidden = false;
            }
            if (isOpen && cancelAppointmentButton && canCancel) {
                cancelAppointmentButton.hidden = false;
            }

            // Spec 024 (CA5, CA7): only an open appointment gets in, so only it shows its QR code.
            if (isOpen) {
                showQr(appointmentId, appointment.date, appointment.time);
            }

            // Spec 018 (CA3, CA9): the administrator reads every clinical record, the doctor only
            // the ones of their own appointments and the assistant none; the API refuses the rest.
            var readsTracking = trackingScope === 'all'
                || (trackingScope === 'own' && actorId !== '' && String(appointment.user_id || '') === actorId);

            if (normalizedStatus === 'completada' && readsTracking) {
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

                    // Una cita completada puede no tener registro clínico guardado: no es un error.
                    if (trackingError.status === 404) {
                        var noRecord = document.createElement('p');
                        noRecord.className = 'text-sm text-muted';
                        noRecord.textContent = 'Esta cita no tiene seguimiento registrado.';
                        clinicalBody.appendChild(noRecord);
                        return;
                    }

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
        resetQr();
        closeDialog(modal);
        activeAppointmentId = null;
        activeAppointmentSummary = null;
    }

    if (qrDownload) {
        qrDownload.addEventListener('click', downloadQr);
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

    if (cancelAppointmentButton) {
        cancelAppointmentButton.addEventListener('click', function () {
            if (activeAppointmentSummary && window.agendaCancelAppointment && typeof window.agendaCancelAppointment.open === 'function') {
                window.agendaCancelAppointment.open(activeAppointmentSummary, cancelAppointmentButton);
            }
        });
    }

    window.agendaViewAppointmentModal = {
        open: open,
    };
})();
