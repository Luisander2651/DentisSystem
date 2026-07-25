(function () {
    if (window.__agendaCreateAppointmentInit) {
        return;
    }

    window.__agendaCreateAppointmentInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function toMinutes(value) {
        var parts = String(value || '').split(':');

        if (parts.length < 2) {
            return null;
        }

        var hours = parseInt(parts[0], 10);
        var minutes = parseInt(parts[1], 10);

        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return null;
        }

        return (hours * 60) + minutes;
    }

    function fromMinutes(totalMinutes) {
        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;

        return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
    }

    function escapeText(value) {
        return String(value || '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    var openButtons = document.querySelectorAll('[data-create-appointment-open]');
    var modal = document.querySelector('[data-create-appointment-modal]');
    var editModal = document.querySelector('[data-edit-appointment-modal]');

    if (!modal) {
        return;
    }

    var isAdmin = modal.getAttribute('data-create-appointment-is-admin') === 'true';
    var form = modal.querySelector('[data-create-appointment-form]');
    var treatmentSelect = modal.querySelector('[data-create-appointment-treatment-select]');
    var dateInput = modal.querySelector('[data-create-appointment-date]');
    var timeSelect = modal.querySelector('[data-create-appointment-time-select]');
    var patientSelect = modal.querySelector('[data-create-appointment-patient-select]');
    var doctorSelect = modal.querySelector('[data-create-appointment-doctor-select]');
    var patientHidden = modal.querySelector('[data-create-appointment-patient-hidden]');
    var userHidden = modal.querySelector('[data-create-appointment-user-hidden]');
    var cancelButtons = modal.querySelectorAll('[data-create-appointment-cancel]');
    var submitButton = modal.querySelector('[data-create-appointment-submit]');
    var errorBox = modal.querySelector('[data-create-appointment-error]');

    var editForm = editModal ? editModal.querySelector('[data-edit-appointment-form]') : null;
    var editDateInput = editModal ? editModal.querySelector('[data-edit-appointment-date]') : null;
    var editTimeInput = editModal ? editModal.querySelector('[data-edit-appointment-time]') : null;
    var editStatusSelect = editModal ? editModal.querySelector('[data-edit-appointment-status]') : null;
    var editWhatsappCheckbox = editModal ? editModal.querySelector('[data-edit-appointment-whatsapp-reminder]') : null;
    var editCancelButtons = editModal ? editModal.querySelectorAll('[data-edit-appointment-cancel]') : [];
    var editSubmitButton = editModal ? editModal.querySelector('[data-edit-appointment-submit]') : null;
    var editErrorBox = editModal ? editModal.querySelector('[data-edit-appointment-error]') : null;
    var editSummary = editModal ? editModal.querySelector('[data-edit-appointment-summary]') : null;

    if (!form || !treatmentSelect || !dateInput || !timeSelect || !cancelButtons.length || !submitButton || !errorBox) {
        return;
    }

    var appointmentsForToday = [];
    var treatments = [];
    var isSubmitting = false;
    var isEditingSubmitting = false;
    var activeAppointmentId = null;
    var timeStep = 15;
    var openingHour = 8 * 60;
    var closingHour = 18 * 60;

    function todayString() {
        return new Date().toISOString().split('T')[0];
    }

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
            reprogramada: 'reprogramada'
        };

        return mapping[normalized] || 'asignada';
    }

    function showModalError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function hideModalError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function showEditModalError(message) {
        if (editErrorBox) {
            editErrorBox.textContent = message;
            editErrorBox.classList.remove('hidden');
        }
    }

    function hideEditModalError() {
        if (editErrorBox) {
            editErrorBox.textContent = '';
            editErrorBox.classList.add('hidden');
        }
    }

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        submitButton.disabled = submitting || !isFormValid();
        cancelButtons.forEach(function (btn) {
            btn.disabled = submitting;
        });
    }

    function setEditSubmittingState(submitting) {
        isEditingSubmitting = submitting;
        if (editSubmitButton) {
            editSubmitButton.disabled = submitting;
        }
        editCancelButtons.forEach(function (btn) {
            btn.disabled = submitting;
        });
    }

    function setOptions(select, items, placeholder, renderOption) {
        var html = ['<option value="">' + placeholder + '</option>'];

        items.forEach(function (item) {
            html.push(renderOption(item));
        });

        select.innerHTML = html.join('');
    }

    function isTodaySelected() {
        return dateInput.value === todayString();
    }

    function getCurrentMinutes() {
        var now = new Date();
        return (now.getHours() * 60) + now.getMinutes();
    }

    function getNextSlotMinutes(minutes) {
        return Math.ceil(minutes / timeStep) * timeStep;
    }

    function getTreatmentDuration() {
        var selectedTreatment = treatments.find(function (item) {
            return String(item.id) === String(treatmentSelect.value);
        });

        return selectedTreatment ? parseInt(selectedTreatment.time || 0, 10) || 0 : 0;
    }

    function getBlockedIntervals() {
        var intervals = [];

        if (!isTodaySelected()) {
            return intervals;
        }

        appointmentsForToday.forEach(function (appointment) {
            var startMinutes = toMinutes(appointment.time);
            var treatmentMinutes = parseInt(appointment.treatment_time || 0, 10) || 0;

            if (startMinutes === null || treatmentMinutes <= 0) {
                return;
            }

            intervals.push({
                start: startMinutes,
                end: startMinutes + treatmentMinutes,
            });
        });

        return intervals;
    }

    function buildOccupiedSlots(intervals) {
        var occupiedSlots = {};

        intervals.forEach(function (interval) {
            for (var minute = interval.start; minute < interval.end; minute += timeStep) {
                occupiedSlots[minute] = true;
            }
        });

        return occupiedSlots;
    }

    function isTimeSlotAvailable(startMinutes, durationMinutes, occupiedSlots) {
        if (durationMinutes <= 0) {
            return false;
        }

        if (startMinutes < openingHour) {
            return false;
        }

        if ((startMinutes + durationMinutes) > closingHour) {
            return false;
        }

        for (var minute = startMinutes; minute < (startMinutes + durationMinutes); minute += timeStep) {
            if (occupiedSlots[minute]) {
                return false;
            }
        }

        return true;
    }

    function renderTimeOptions() {
        var durationMinutes = getTreatmentDuration();
        var blockedIntervals = getBlockedIntervals();
        var occupiedSlots = buildOccupiedSlots(blockedIntervals);
        var options = ['<option value="">Selecciona una hora</option>'];
        var startMinute = openingHour;

        if (isTodaySelected()) {
            startMinute = Math.max(openingHour, getNextSlotMinutes(getCurrentMinutes()));
        }

        if (durationMinutes <= 0) {
            timeSelect.innerHTML = '<option value="">Selecciona un tratamiento primero</option>';
            timeSelect.disabled = true;
            updateSubmitState();
            return;
        }

        timeSelect.disabled = false;

        for (var minute = startMinute; minute <= (closingHour - durationMinutes); minute += timeStep) {
            var value = fromMinutes(minute);
            var available = isTimeSlotAvailable(minute, durationMinutes, occupiedSlots);

            options.push('<option value="' + value + '"' + (available ? '' : ' disabled') + '>' + value + (available ? '' : ' (ocupado)') + '</option>');
        }

        timeSelect.innerHTML = options.join('');

        if (timeSelect.selectedOptions.length && timeSelect.selectedOptions[0].disabled) {
            timeSelect.value = '';
        }

        updateSubmitState();
    }

    function isFormValid() {
        var hasAdminSelection = !isAdmin || (!!patientSelect && !!doctorSelect && patientSelect.value.trim() !== '' && doctorSelect.value.trim() !== '');
        var hasHiddenValues = isAdmin || ((patientHidden ? patientHidden.value.trim() !== '' : true) && (userHidden ? userHidden.value.trim() !== '' : true));

        return hasAdminSelection
            && hasHiddenValues
            && treatmentSelect.value.trim() !== ''
            && dateInput.value.trim() !== ''
            && timeSelect.value.trim() !== '';
    }

    function updateSubmitState() {
        submitButton.disabled = isSubmitting || !isFormValid();
    }

    function resetFormState() {
        hideModalError();
        form.reset();

        if (isAdmin) {
            if (patientSelect) {
                patientSelect.value = '';
            }

            if (doctorSelect) {
                doctorSelect.value = '';
            }
        }

        dateInput.value = todayString();
        renderTimeOptions();
        updateSubmitState();
    }

    async function openModal() {
        resetFormState();
        await refreshAvailability();
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(force) {
        if (isSubmitting && !force) {
            return;
        }

        resetFormState();
        modal.classList.add('hidden');
        document.body.style.overflow = '';
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

    async function createAppointment(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/appointments', {
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
            throw new Error(data.error || data.message || 'No se pudo crear la cita.');
        }
    }

    async function updateAppointment(payload) {
        if (!activeAppointmentId) {
            throw new Error('No hay una cita seleccionada para editar.');
        }

        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/appointments/' + activeAppointmentId, {
            method: 'PUT',
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
            throw new Error(data.error || data.message || 'No se pudo actualizar la cita.');
        }
    }

    async function loadPatients() {
        var data = await requestJson('/api/v1/agenda/patients');
        var patients = data.data || [];

        if (patientSelect) {
            setOptions(patientSelect, patients, 'Selecciona un paciente', function (patient) {
                return '<option value="' + escapeText(patient.id) + '">' + escapeText(patient.first_name + ' ' + patient.last_name) + ' - ' + escapeText(patient.email) + '</option>';
            });
        }
    }

    async function loadDoctors() {
        var data = await requestJson('/api/v1/agenda/doctors');
        var doctors = data.data || [];

        if (doctorSelect) {
            setOptions(doctorSelect, doctors, 'Selecciona un doctor', function (doctor) {
                return '<option value="' + escapeText(doctor.id) + '">' + escapeText(doctor.first_name + ' ' + doctor.last_name) + ' - ' + escapeText(doctor.email) + '</option>';
            });
        }
    }

    async function loadTreatments() {
        var data = await requestJson('/api/v1/agenda/treatments');
        treatments = data.data || [];

        setOptions(treatmentSelect, treatments, 'Selecciona un tratamiento', function (treatment) {
            var durationLabel = treatment.time ? treatment.time + ' min' : 'sin duración';
            return '<option value="' + escapeText(treatment.id) + '">' + escapeText(treatment.name) + ' - ' + escapeText(durationLabel) + '</option>';
        });
    }

    async function loadTodayAppointments() {
        var data = await requestJson('/api/v1/agenda/today-appointments');
        appointmentsForToday = data.data || [];
    }

    async function refreshAvailability() {
        await loadTodayAppointments();
        renderTimeOptions();
    }

    async function loadModalData() {
        var tasks = [loadTreatments(), loadTodayAppointments()];

        if (isAdmin) {
            tasks.push(loadPatients(), loadDoctors());
        }

        await Promise.all(tasks);
    }

    function buildPayload() {
        return {
            patient_id: isAdmin ? patientSelect.value.trim() : (patientHidden ? patientHidden.value.trim() : ''),
            user_id: isAdmin ? doctorSelect.value.trim() : (userHidden ? userHidden.value.trim() : ''),
            treatment_id: treatmentSelect.value.trim(),
            date: dateInput.value.trim(),
            time: timeSelect.value.trim(),
        };
    }

    function buildEditPayload() {
        return {
            date: editDateInput ? editDateInput.value.trim() : '',
            time: editTimeInput ? editTimeInput.value.trim() : '',
            status: editStatusSelect ? editStatusSelect.value.trim() : '',
            whatsapp_reminder: editWhatsappCheckbox ? editWhatsappCheckbox.checked : false,
        };
    }

    function resetEditFormState() {
        hideEditModalError();
        if (editForm) {
            editForm.reset();
        }
        if (editDateInput) {
            editDateInput.value = todayString();
        }
        if (editSummary) {
            editSummary.textContent = 'Actualiza la fecha, la hora o el estado de la cita.';
        }
        if (editStatusSelect) {
            editStatusSelect.value = 'asignada';
        }
        if (editWhatsappCheckbox) {
            editWhatsappCheckbox.checked = false;
        }
        activeAppointmentId = null;
    }

    async function openEditAppointment(appointmentId) {
        resetEditFormState();
        activeAppointmentId = appointmentId;

        try {
            var data = await requestJson('/api/v1/appointments/' + appointmentId);
            var appointment = data.data || {};

            if (editDateInput) {
                editDateInput.value = appointment.date || todayString();
            }
            if (editTimeInput) {
                editTimeInput.value = appointment.time || '';
            }
            if (editStatusSelect) {
                editStatusSelect.value = normalizeAppointmentStatus(appointment.status);
            }
            if (editWhatsappCheckbox) {
                editWhatsappCheckbox.checked = Boolean(appointment.whatsapp_reminder);
            }
            if (editSummary) {
                var patientName = appointment.patient_name || 'Paciente';
                var treatmentName = appointment.treatment_name || 'Consulta';
                editSummary.textContent = patientName + ' • ' + treatmentName;
            }
        } catch (error) {
            showEditModalError(error.message || 'No se pudo cargar la cita para editar.');
            return;
        }

        if (editModal) {
            editModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeEditModal(force) {
        if (isEditingSubmitting && !force) {
            return;
        }

        resetEditFormState();
        if (editModal) {
            editModal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    if (isAdmin) {
        [patientSelect, doctorSelect].forEach(function (select) {
            if (!select) {
                return;
            }

            select.addEventListener('change', updateSubmitState);
        });
    }

    treatmentSelect.addEventListener('change', renderTimeOptions);
    dateInput.addEventListener('change', renderTimeOptions);
    timeSelect.addEventListener('change', updateSubmitState);

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting || !isFormValid()) {
            return;
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await createAppointment(buildPayload());
        } catch (error) {
            showModalError(error.message || 'No se pudo crear la cita.');
            return;
        } finally {
            setSubmittingState(false);
        }

        closeModal(true);

        if (window.agendaPage && typeof window.agendaPage.reload === 'function') {
            window.agendaPage.reload().catch(function (reloadError) {
                console.error('No se pudo recargar la agenda tras crear la cita:', reloadError);
            });
        }
    });

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    openButtons.forEach(function (button) {
        button.addEventListener('click', openModal);
    });

    if (editForm) {
        editForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isEditingSubmitting || !activeAppointmentId) {
                return;
            }

            hideEditModalError();
            setEditSubmittingState(true);

            try {
                await updateAppointment(buildEditPayload());
            } catch (error) {
                showEditModalError(error.message || 'No se pudo actualizar la cita.');
                return;
            } finally {
                setEditSubmittingState(false);
            }

            closeEditModal(true);

            if (window.agendaPage && typeof window.agendaPage.reload === 'function') {
                window.agendaPage.reload().catch(function (reloadError) {
                    console.error('No se pudo recargar la agenda tras editar la cita:', reloadError);
                });
            }
        });
    }

    editCancelButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            closeEditModal();
        });
    });

    window.agendaAppointmentModal = {
        open: openModal,
        close: closeModal,
        refreshAvailability: refreshAvailability,
        loadData: loadModalData,
        openEdit: openEditAppointment,
        closeEdit: closeEditModal,
    };

    dateInput.value = todayString();
    updateSubmitState();

    loadModalData().catch(function (error) {
        showModalError(error.message || 'No se pudieron cargar los datos de la cita.');
    });
})();