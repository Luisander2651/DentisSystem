import { closeDialog, isBackdropClick, openDialog } from '../../ui/dialog';
import { announce, announceError, clearAnnouncements, failureMessage } from '../../ui/status';

(function () {
    if (window.__agendaCompleteAppointmentInit) {
        return;
    }

    window.__agendaCompleteAppointmentInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function parseArrayField(value) {
        var text = String(value || '').trim();

        if (text === '') {
            return [];
        }

        return text
            .split(/[\n,]/)
            .map(function (item) { return item.trim(); })
            .filter(function (item) { return item !== ''; });
    }

    var modal = document.querySelector('[data-complete-appointment-modal]');

    if (!modal) {
        return;
    }

    var form = modal.querySelector('[data-complete-appointment-form]');
    var summaryEl = modal.querySelector('[data-complete-appointment-summary]');
    var stepIndicator = modal.querySelector('[data-complete-appointment-step-indicator]');
    var step1El = modal.querySelector('[data-complete-appointment-step="1"]');
    var step2El = modal.querySelector('[data-complete-appointment-step="2"]');
    var reasonInput = modal.querySelector('[data-complete-appointment-reason]');
    var symptomsInput = modal.querySelector('[data-complete-appointment-symptoms]');
    var diagnosisInput = modal.querySelector('[data-complete-appointment-diagnosis]');
    var procedureInput = modal.querySelector('[data-complete-appointment-procedure]');
    var observationsInput = modal.querySelector('[data-complete-appointment-observations]');
    var recommendationsInput = modal.querySelector('[data-complete-appointment-recommendations]');
    var addPrescriptionButton = modal.querySelector('[data-complete-appointment-add-prescription]');
    var prescriptionsEmpty = modal.querySelector('[data-complete-appointment-prescriptions-empty]');
    var prescriptionsList = modal.querySelector('[data-complete-appointment-prescriptions-list]');
    var errorBox = modal.querySelector('[data-complete-appointment-error]') || document.createElement('p');
    var cancelButtons = modal.querySelectorAll('[data-complete-appointment-cancel]');
    var backButton = modal.querySelector('[data-complete-appointment-back]');
    var nextButton = modal.querySelector('[data-complete-appointment-next]');
    var submitButton = modal.querySelector('[data-complete-appointment-submit]');

    var confirmDialog = document.querySelector('[data-complete-appointment-confirm]');
    var confirmTitle = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-title]') : null;
    var confirmMessage = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-message]') : null;
    var confirmAcceptButton = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-accept]') : null;
    var confirmAcceptText = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-accept-text]') : null;
    var confirmCancelText = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-cancel-text]') : null;
    var confirmCancelButtons = confirmDialog ? confirmDialog.querySelectorAll('[data-complete-appointment-confirm-cancel]') : [];

    if (!form || !step1El || !step2El || !reasonInput || !diagnosisInput || !procedureInput || !prescriptionsList) {
        return;
    }

    // Spec 020 (CA8, A20): the same dialog registers the follow-up of an appointment already
    // completed. Without `mode` it completes, as always (A21).
    var MODES = {
        complete: {
            title: 'Completar cita',
            path: '/complete',
            confirmTitle: '¿Completar esta cita?',
            confirmMessage: 'Se guardará el registro clínico y las prescripciones. Esta acción no se puede deshacer.',
            acceptText: 'Sí, completar cita',
            done: 'Cita completada.',
        },
        register: {
            title: 'Registrar seguimiento',
            path: '/tracking',
            confirmTitle: '¿Registrar el seguimiento de esta cita?',
            confirmMessage: 'Se guardará el registro clínico y las prescripciones. La cita sigue completada.',
            acceptText: 'Sí, registrar seguimiento',
            done: 'Seguimiento registrado.',
        },
    };
    var modalTitle = modal.querySelector('h2');
    var mode = MODES.complete;

    var activeAppointmentId = null;
    var currentStep = 1;
    var isDirty = false;
    var isSubmitting = false;
    var pendingConfirmAccept = null;
    var prescriptionCount = 0;

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
        clearAnnouncements();
    }

    function updatePrescriptionsEmptyState() {
        if (!prescriptionsEmpty) return;
        var hasRows = prescriptionsList.querySelectorAll('[data-prescription-row]').length > 0;
        prescriptionsEmpty.classList.toggle('hidden', hasRows);
    }

    function createPrescriptionRow() {
        prescriptionCount += 1;

        var id = 'prescription-' + prescriptionCount;
        var input = function (name, text, attributes, required) {
            return '<div' + (name === 'instructions' ? ' class="sm:col-span-2"' : '') + '>'
                + '<label for="' + id + '-' + name + '" class="mb-1.5 block text-sm font-semibold text-ink">' + text + (required ? ' <span class="text-danger" title="Obligatorio">*</span>' : '') + '</label>'
                + '<input id="' + id + '-' + name + '" data-prescription-' + name + (required ? ' data-prescription-required' : '') + ' class="block h-control w-full rounded-control border border-field bg-surface px-3 text-control text-ink" ' + attributes + '>'
                + '</div>';
        };

        var row = document.createElement('fieldset');
        row.className = 'rounded-box border border-line bg-surface p-3';
        row.setAttribute('data-prescription-row', '');

        row.innerHTML = ''
            + '<legend class="px-1 text-sm font-bold text-ink">Prescripción</legend>'
            + '<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">'
            + input('medication', 'Medicamento', 'type="text" autocomplete="off"', true)
            + input('dosage', 'Dosis', 'type="text" autocomplete="off"', true)
            + input('duration', 'Duración en días', 'type="number" min="1" max="365" inputmode="numeric"', true)
            + input('frequency', 'Veces al día', 'type="number" min="1" max="24" inputmode="numeric"', true)
            + input('instructions', 'Instrucciones (opcional)', 'type="text" autocomplete="off"', false)
            + '</div>'
            + '<button type="button" data-prescription-remove class="mt-3 inline-flex min-h-control items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-danger-soft hover:text-danger">Quitar esta prescripción</button>';

        var removeButton = row.querySelector('[data-prescription-remove]');
        removeButton.addEventListener('click', function () {
            row.remove();
            isDirty = true;
            updatePrescriptionsEmptyState();
        });

        return row;
    }

    function addPrescriptionRow() {
        prescriptionsList.appendChild(createPrescriptionRow());
        updatePrescriptionsEmptyState();
    }

    function showStep(step) {
        currentStep = step;
        step1El.classList.toggle('hidden', step !== 1);
        step2El.classList.toggle('hidden', step !== 2);

        cancelButtons.forEach(function (button) {
            button.hidden = step !== 1;
        });

        if (backButton) backButton.hidden = step !== 2;
        if (nextButton) nextButton.hidden = step !== 1;
        if (submitButton) submitButton.hidden = step !== 2;

        if (stepIndicator) {
            stepIndicator.textContent = step === 1
                ? 'Paso 1 de 2 · Datos clínicos'
                : 'Paso 2 de 2 · Prescripciones';
        }

        hideError();
    }

    function clearFieldError(field) {
        var message = document.getElementById(field.id + '-error');

        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
        if (message) message.remove();
    }

    function setFieldError(field, text) {
        clearFieldError(field);

        var message = document.createElement('p');
        message.id = field.id + '-error';
        message.className = 'mt-1 text-sm font-semibold text-danger';
        message.textContent = text;

        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', message.id);
        field.insertAdjacentElement('afterend', message);
    }

    function validateStep1() {
        var required = [
            [reasonInput, 'El motivo de la consulta es obligatorio.'],
            [diagnosisInput, 'El diagnóstico es obligatorio.'],
            [procedureInput, 'El procedimiento realizado es obligatorio.'],
        ];
        var invalid = [];

        required.forEach(function (pair) {
            if (pair[0].value.trim()) {
                clearFieldError(pair[0]);
                return;
            }

            setFieldError(pair[0], pair[1]);
            invalid.push(pair);
        });

        return invalid;
    }

    function collectPrescriptions() {
        return Array.from(prescriptionsList.querySelectorAll('[data-prescription-row]'))
            .filter(function (row) {
                var medication = row.querySelector('[data-prescription-medication]').value.trim();
                var dosage = row.querySelector('[data-prescription-dosage]').value.trim();
                var duration = row.querySelector('[data-prescription-duration]').value.trim();
                var frequency = row.querySelector('[data-prescription-frequency]').value.trim();
                var instructions = row.querySelector('[data-prescription-instructions]').value.trim();

                // El mismo criterio que syncPrescriptionRequired: con las indicaciones solas, la fila
                // también cuenta y sus datos obligatorios se piden (spec 016, CA37).
                return medication || dosage || duration || frequency || instructions;
            })
            .map(function (row) {
                var instructions = row.querySelector('[data-prescription-instructions]').value.trim();
                var prescription = {
                    medication: row.querySelector('[data-prescription-medication]').value.trim(),
                    dosage: row.querySelector('[data-prescription-dosage]').value.trim(),
                    durationDays: parseInt(row.querySelector('[data-prescription-duration]').value, 10),
                    dailyFrequency: parseInt(row.querySelector('[data-prescription-frequency]').value, 10),
                };

                if (instructions) {
                    prescription.instructions = instructions;
                }

                return prescription;
            });
    }

    // Una fila vacía no cuenta como prescripción; en cuanto tiene algo, sus cuatro datos son
    // obligatorios. Los que fallan quedan marcados uno a uno (spec 016, CA37).
    var PRESCRIPTION_RULES = [
        ['[data-prescription-medication]', 'Escribe el medicamento.', function (value) { return value !== ''; }],
        ['[data-prescription-dosage]', 'Escribe la dosis.', function (value) { return value !== ''; }],
        ['[data-prescription-duration]', 'Escribe una duración de 1 a 365 días.', function (value) { return /^\d+$/.test(value) && value >= 1 && value <= 365; }],
        ['[data-prescription-frequency]', 'Escribe de 1 a 24 veces al día.', function (value) { return /^\d+$/.test(value) && value >= 1 && value <= 24; }],
    ];

    function syncPrescriptionRequired(row) {
        var filled = Array.from(row.querySelectorAll('input')).some(function (field) { return field.value.trim() !== ''; });

        row.querySelectorAll('[data-prescription-required]').forEach(function (field) {
            field.required = filled;
            field.setAttribute('aria-required', filled ? 'true' : 'false');
        });

        return filled;
    }

    function markPrescriptionFields() {
        var firstInvalid = null;

        Array.from(prescriptionsList.querySelectorAll('[data-prescription-row]')).forEach(function (row) {
            var filled = syncPrescriptionRequired(row);

            PRESCRIPTION_RULES.forEach(function (rule) {
                var field = row.querySelector(rule[0]);
                clearFieldError(field);

                if (filled && !rule[2](field.value.trim())) {
                    setFieldError(field, rule[1]);
                    firstInvalid = firstInvalid || field;
                }
            });
        });

        return firstInvalid;
    }

    function validatePrescriptions(prescriptions) {
        var errors = [];

        prescriptions.forEach(function (prescription, index) {
            var isValid = prescription.medication
                && prescription.dosage
                && Number.isInteger(prescription.durationDays) && prescription.durationDays >= 1 && prescription.durationDays <= 365
                && Number.isInteger(prescription.dailyFrequency) && prescription.dailyFrequency >= 1 && prescription.dailyFrequency <= 24;

            if (!isValid) {
                errors.push('Prescripción ' + (index + 1) + ': completa medicamento, dosis, duración (1-365 días) y frecuencia (1-24 veces al día).');
            }
        });

        return errors;
    }

    function buildPayload() {
        return {
            reason: reasonInput.value.trim(),
            symptoms: parseArrayField(symptomsInput ? symptomsInput.value : ''),
            diagnosis: diagnosisInput.value.trim(),
            procedure_performed: procedureInput.value.trim(),
            observations: observationsInput && observationsInput.value.trim() ? observationsInput.value.trim() : null,
            recommendations: recommendationsInput && recommendationsInput.value.trim() ? recommendationsInput.value.trim() : null,
            prescriptions: collectPrescriptions(),
        };
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

    /** A 422 of the server, kept so each message goes under its field. */
    function ValidationFailure(message, errors) {
        this.message = message;
        this.errors = errors || {};
    }

    async function completeAppointment(appointmentId, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/appointments/' + appointmentId + mode.path, {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
            body: JSON.stringify(payload),
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (response.status === 422 && data.errors) {
            throw new ValidationFailure(failureMessage(response.status, data), data.errors);
        }

        if (!response.ok) {
            throw new Error(failureMessage(response.status, data));
        }
    }

    /**
     * Spec 020 (CA8, A40): each message of a 422 under its field, `symptoms.N` under the symptoms
     * and `prescriptions.N.*` under that row; the dialog goes back to the step of the first one.
     */
    function markServerErrors(errors) {
        var prescriptionFields = {
            medication: '[data-prescription-medication]',
            dosage: '[data-prescription-dosage]',
            durationDays: '[data-prescription-duration]',
            dailyFrequency: '[data-prescription-frequency]',
        };
        var rows = Array.from(prescriptionsList.querySelectorAll('[data-prescription-row]'));
        var firstStep = null;
        var firstField = null;

        Object.keys(errors).forEach(function (key) {
            var message = [].concat(errors[key])[0];
            var parts = key.split('.');
            var field = null;
            var step = 1;

            if (key === 'reason') field = reasonInput;
            if (key === 'diagnosis') field = diagnosisInput;
            if (key === 'procedure_performed') field = procedureInput;
            if (parts[0] === 'symptoms') field = symptomsInput;

            if (parts[0] === 'prescriptions' && parts.length === 3 && rows[Number(parts[1])]) {
                field = rows[Number(parts[1])].querySelector(prescriptionFields[parts[2]] || '[data-prescription-medication]');
                step = 2;
            }

            if (field && message) {
                setFieldError(field, message);

                if (firstField === null) {
                    firstField = field;
                    firstStep = step;
                }
            }
        });

        if (firstField) {
            showStep(firstStep);
            firstField.focus();
        }
    }

    function openConfirm(options) {
        if (!confirmDialog) {
            if (typeof options.onAccept === 'function') options.onAccept();
            return;
        }

        if (confirmTitle) confirmTitle.textContent = options.title;
        if (confirmMessage) confirmMessage.textContent = options.message;
        if (confirmAcceptText) confirmAcceptText.textContent = options.acceptText;
        if (confirmCancelText) confirmCancelText.textContent = options.cancelText;
        pendingConfirmAccept = options.onAccept;
        openDialog(confirmDialog);
    }

    function closeConfirm() {
        if (confirmDialog) closeDialog(confirmDialog);
        pendingConfirmAccept = null;
    }

    function resetState() {
        hideError();
        form.reset();
        [reasonInput, diagnosisInput, procedureInput].forEach(clearFieldError);
        prescriptionsList.innerHTML = '';
        updatePrescriptionsEmptyState();
        isDirty = false;
        activeAppointmentId = null;
        if (summaryEl) summaryEl.textContent = 'Paciente • Tratamiento';
        closeConfirm();
    }

    function closeModal(force) {
        if (isSubmitting && !force) return;
        closeDialog(modal);
        closeConfirm();
    }

    function requestClose() {
        if (isSubmitting) return;

        if (isDirty) {
            openConfirm({
                title: '¿Descartar cambios?',
                message: 'Se perderá la información capturada en este formulario.',
                acceptText: 'Descartar',
                cancelText: 'Seguir editando',
                onAccept: function () {
                    closeModal(true);
                },
            });
            return;
        }

        closeModal(true);
    }

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        if (submitButton) submitButton.disabled = submitting;
        if (backButton) backButton.disabled = submitting;
    }

    async function submitCompletion() {
        if (isSubmitting || !activeAppointmentId) return;

        hideError();
        setSubmittingState(true);

        try {
            await completeAppointment(activeAppointmentId, buildPayload());
        } catch (error) {
            showError(error instanceof TypeError ? failureMessage(0) : error.message);
            setSubmittingState(false);

            if (error instanceof ValidationFailure) {
                markServerErrors(error.errors);

                return;
            }

            // Al cerrarse la confirmación el botón estaba deshabilitado y el foco se fue a otro sitio.
            if (submitButton) {
                submitButton.focus();
            }

            return;
        }

        setSubmittingState(false);
        closeModal(true);
        announce(mode.done);

        if (window.agendaPage && typeof window.agendaPage.reload === 'function') {
            window.agendaPage.reload().catch(function (reloadError) {
                console.error('No se pudo recargar la agenda tras completar la cita:', reloadError);
            });
        }
    }

    /**
     * @param {string} appointmentId
     * @param {HTMLElement} [opener]
     * @param {{mode?: 'register'}} [options] `register` records the follow-up of a completed appointment
     */
    async function open(appointmentId, opener, options) {
        resetState();
        mode = options && options.mode === 'register' ? MODES.register : MODES.complete;

        if (modalTitle) {
            modalTitle.textContent = mode.title;
        }

        activeAppointmentId = appointmentId;
        openDialog(modal, opener || document.activeElement);
        showStep(1);

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};

            if (summaryEl) {
                summaryEl.textContent = (appointment.patient_name || 'Paciente') + ' • ' + (appointment.treatment_name || 'Consulta');
            }
        } catch (error) {
            showError(error instanceof TypeError ? failureMessage(0) : error.message);
        }
    }

    form.addEventListener('input', function (event) {
        isDirty = true;

        var row = event.target.closest ? event.target.closest('[data-prescription-row]') : null;

        if (row) {
            syncPrescriptionRequired(row);
        }

        if (event.target.getAttribute('aria-invalid') === 'true' && event.target.value.trim()) {
            clearFieldError(event.target);
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
    });

    // Escape, el botón de cerrar y el clic fuera del diálogo preguntan, como "Cancelar", si hay
    // datos sin guardar: un descuido no borra lo capturado.
    modal.addEventListener('cancel', function (event) {
        event.preventDefault();
        requestClose();
    });

    // El botón de cerrar y el clic fuera del diálogo.
    modal.addEventListener('click', function (event) {
        if (isBackdropClick(event, modal) || event.target.closest('[data-dialog-close]')) {
            event.stopPropagation();
            requestClose();
        }
    });

    if (addPrescriptionButton) {
        addPrescriptionButton.addEventListener('click', addPrescriptionRow);
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            hideError();
            var errors = validateStep1();

            if (errors.length) {
                showError(errors[0][1]);
                errors[0][0].focus();
                return;
            }

            showStep(2);
            if (addPrescriptionButton) addPrescriptionButton.focus();
        });
    }

    if (backButton) {
        backButton.addEventListener('click', function () {
            showStep(1);
            reasonInput.focus();
        });
    }

    if (submitButton) {
        submitButton.addEventListener('click', function () {
            hideError();
            var prescriptions = collectPrescriptions();
            var errors = validatePrescriptions(prescriptions);

            markPrescriptionFields();

            if (errors.length) {
                showError(errors[0]);
                return;
            }

            openConfirm({
                title: mode.confirmTitle,
                message: mode.confirmMessage,
                acceptText: mode.acceptText,
                cancelText: 'Revisar de nuevo',
                onAccept: submitCompletion,
            });
        });
    }

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', requestClose);
    });

    confirmCancelButtons.forEach(function (button) {
        button.addEventListener('click', closeConfirm);
    });

    if (confirmAcceptButton) {
        confirmAcceptButton.addEventListener('click', function () {
            var action = pendingConfirmAccept;
            closeConfirm();
            if (typeof action === 'function') {
                action();
            }
        });
    }

    window.agendaCompleteAppointmentModal = {
        open: open,
    };
})();
