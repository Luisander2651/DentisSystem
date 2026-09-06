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
    var errorBox = modal.querySelector('[data-complete-appointment-error]');
    var cancelButtons = modal.querySelectorAll('[data-complete-appointment-cancel]');
    var backButton = modal.querySelector('[data-complete-appointment-back]');
    var nextButton = modal.querySelector('[data-complete-appointment-next]');
    var submitButton = modal.querySelector('[data-complete-appointment-submit]');

    var confirmDialog = modal.querySelector('[data-complete-appointment-confirm]');
    var confirmTitle = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-title]') : null;
    var confirmMessage = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-message]') : null;
    var confirmAcceptButton = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-accept]') : null;
    var confirmAcceptText = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-accept-text]') : null;
    var confirmCancelText = confirmDialog ? confirmDialog.querySelector('[data-complete-appointment-confirm-cancel-text]') : null;
    var confirmCancelButtons = confirmDialog ? confirmDialog.querySelectorAll('[data-complete-appointment-confirm-cancel]') : [];

    if (!form || !step1El || !step2El || !reasonInput || !diagnosisInput || !procedureInput || !prescriptionsList) {
        return;
    }

    var activeAppointmentId = null;
    var currentStep = 1;
    var isDirty = false;
    var isSubmitting = false;
    var pendingConfirmAccept = null;

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

    function updatePrescriptionsEmptyState() {
        if (!prescriptionsEmpty) return;
        var hasRows = prescriptionsList.querySelectorAll('[data-prescription-row]').length > 0;
        prescriptionsEmpty.classList.toggle('hidden', hasRows);
    }

    function createPrescriptionRow() {
        var row = document.createElement('div');
        row.className = 'rounded-2xl border border-slate-200 bg-white p-4';
        row.setAttribute('data-prescription-row', '');

        row.innerHTML = ''
            + '<div class="flex items-start justify-between gap-3">'
            + '  <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">'
            + '    <div>'
            + '      <label class="mb-1 block text-xs font-bold text-slate-600">Medicamento</label>'
            + '      <input type="text" data-prescription-medication class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10" placeholder="Ej. Amoxicilina 500mg">'
            + '    </div>'
            + '    <div>'
            + '      <label class="mb-1 block text-xs font-bold text-slate-600">Dosis</label>'
            + '      <input type="text" data-prescription-dosage class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10" placeholder="Ej. 1 tableta">'
            + '    </div>'
            + '    <div>'
            + '      <label class="mb-1 block text-xs font-bold text-slate-600">Duración (días)</label>'
            + '      <input type="number" min="1" max="365" data-prescription-duration class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10" placeholder="Ej. 7">'
            + '    </div>'
            + '    <div>'
            + '      <label class="mb-1 block text-xs font-bold text-slate-600">Frecuencia (veces/día)</label>'
            + '      <input type="number" min="1" max="24" data-prescription-frequency class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10" placeholder="Ej. 3">'
            + '    </div>'
            + '    <div class="sm:col-span-2">'
            + '      <label class="mb-1 block text-xs font-bold text-slate-600">Instrucciones <span class="font-normal text-slate-400">(opcional)</span></label>'
            + '      <input type="text" data-prescription-instructions class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-900 focus:border-[#B5114A] focus:outline-none focus:ring-4 focus:ring-[#B5114A]/10" placeholder="Ej. Tomar despues de alimentos">'
            + '    </div>'
            + '  </div>'
            + '  <button type="button" data-prescription-remove class="cursor-pointer rounded-xl p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Quitar prescripcion">'
            + '    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">'
            + '      <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m2 0v12a2 2 0 01-2 2H8a2 2 0 01-2-2V7h12z" />'
            + '    </svg>'
            + '  </button>'
            + '</div>';

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

        var cancelButtonOnly = modal.querySelector('form [data-complete-appointment-cancel]');
        if (cancelButtonOnly) {
            cancelButtonOnly.classList.toggle('hidden', step !== 1);
        }

        if (backButton) backButton.classList.toggle('hidden', step !== 2);
        if (nextButton) nextButton.classList.toggle('hidden', step !== 1);
        if (submitButton) submitButton.classList.toggle('hidden', step !== 2);

        if (stepIndicator) {
            stepIndicator.textContent = step === 1
                ? 'Paso 1 de 2 · Datos clínicos'
                : 'Paso 2 de 2 · Prescripciones';
        }

        hideError();
    }

    function validateStep1() {
        var errors = [];

        if (!reasonInput.value.trim()) errors.push('El motivo de la consulta es obligatorio.');
        if (!diagnosisInput.value.trim()) errors.push('El diagnóstico es obligatorio.');
        if (!procedureInput.value.trim()) errors.push('El procedimiento realizado es obligatorio.');

        return errors;
    }

    function collectPrescriptions() {
        return Array.from(prescriptionsList.querySelectorAll('[data-prescription-row]'))
            .filter(function (row) {
                var medication = row.querySelector('[data-prescription-medication]').value.trim();
                var dosage = row.querySelector('[data-prescription-dosage]').value.trim();
                var duration = row.querySelector('[data-prescription-duration]').value.trim();
                var frequency = row.querySelector('[data-prescription-frequency]').value.trim();

                return medication || dosage || duration || frequency;
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
            throw new Error(data.error || data.message || 'No se pudo completar la solicitud.');
        }

        return data;
    }

    async function completeAppointment(appointmentId, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/appointments/' + appointmentId + '/complete', {
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

        if (!response.ok) {
            throw new Error(data.error || data.message || 'No se pudo completar la cita.');
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
        confirmDialog.classList.remove('hidden');
    }

    function closeConfirm() {
        if (confirmDialog) confirmDialog.classList.add('hidden');
        pendingConfirmAccept = null;
    }

    function resetState() {
        hideError();
        form.reset();
        prescriptionsList.innerHTML = '';
        updatePrescriptionsEmptyState();
        isDirty = false;
        activeAppointmentId = null;
        if (summaryEl) summaryEl.textContent = 'Paciente • Tratamiento';
        closeConfirm();
    }

    function closeModal(force) {
        if (isSubmitting && !force) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
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
            showError(error.message || 'No se pudo completar la cita.');
            setSubmittingState(false);
            return;
        }

        setSubmittingState(false);
        closeModal(true);

        if (window.agendaPage && typeof window.agendaPage.reload === 'function') {
            window.agendaPage.reload().catch(function (reloadError) {
                console.error('No se pudo recargar la agenda tras completar la cita:', reloadError);
            });
        }
    }

    async function open(appointmentId) {
        resetState();
        activeAppointmentId = appointmentId;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        showStep(1);

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};

            if (summaryEl) {
                summaryEl.textContent = (appointment.patient_name || 'Paciente') + ' • ' + (appointment.treatment_name || 'Consulta');
            }
        } catch (error) {
            showError(error.message || 'No se pudo cargar la información de la cita.');
        }
    }

    form.addEventListener('input', function () {
        isDirty = true;
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
    });

    if (addPrescriptionButton) {
        addPrescriptionButton.addEventListener('click', addPrescriptionRow);
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            hideError();
            var errors = validateStep1();

            if (errors.length) {
                showError(errors[0]);
                return;
            }

            showStep(2);
        });
    }

    if (backButton) {
        backButton.addEventListener('click', function () {
            showStep(1);
        });
    }

    if (submitButton) {
        submitButton.addEventListener('click', function () {
            hideError();
            var prescriptions = collectPrescriptions();
            var errors = validatePrescriptions(prescriptions);

            if (errors.length) {
                showError(errors[0]);
                return;
            }

            openConfirm({
                title: '¿Completar esta cita?',
                message: 'Se guardará el registro clínico y las prescripciones. Esta acción no se puede deshacer.',
                acceptText: 'Sí, completar cita',
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
