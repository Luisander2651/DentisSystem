(function () {
    if (window.__patientsEditInit) {
        return;
    }

    window.__patientsEditInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function normalizeStatus(status) {
        if (status === 'active' || status === 'inactive') {
            return status;
        }

        if (status === 'Activo') {
            return 'active';
        }

        if (status === 'Inactivo') {
            return 'inactive';
        }

        return 'active';
    }

    var table = document.getElementById('patients-table');
    var modal = document.getElementById('patients-edit-modal');

    if (!table || !modal) {
        return;
    }

    var form = modal.querySelector('[data-edit-patient-form]');
    var patientIdInput = modal.querySelector('[data-edit-patient-id]');
    var firstNameInput = modal.querySelector('[data-edit-patient-first-name]');
    var lastNameInput = modal.querySelector('[data-edit-patient-last-name]');
    var newPasswordInput = modal.querySelector('[data-edit-patient-new-password]');
    var statusSelect = modal.querySelector('[data-edit-patient-status]');
    var cancelButton = modal.querySelector('[data-edit-patient-cancel]');
    var submitButton = modal.querySelector('[data-edit-patient-submit]');
    var errorBox = modal.querySelector('[data-edit-patient-error]');

    if (!form || !patientIdInput || !firstNameInput || !lastNameInput || !newPasswordInput || !statusSelect || !cancelButton || !submitButton || !errorBox) {
        return;
    }

    var isSubmitting = false;
    var originalPatient = {
        id: '',
        firstName: '',
        lastName: '',
        status: 'active',
    };

    function showModalError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function hideModalError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        submitButton.disabled = submitting;
        cancelButton.disabled = submitting;
    }

    function resetPasswordField() {
        var passwordButton = modal.querySelector('[data-toggle-password][data-target-input="patients-edit-new-password"]');
        var openIcon = passwordButton ? passwordButton.querySelector('[data-eye-open]') : null;
        var closeIcon = passwordButton ? passwordButton.querySelector('[data-eye-closed]') : null;

        newPasswordInput.value = '';
        newPasswordInput.type = 'password';

        if (passwordButton) {
            passwordButton.setAttribute('aria-pressed', 'false');
        }

        if (openIcon) {
            openIcon.classList.remove('hidden');
        }

        if (closeIcon) {
            closeIcon.classList.add('hidden');
        }
    }

    function openModal(patientData) {
        hideModalError();

        originalPatient = {
            id: String(patientData.id || '').trim(),
            firstName: String(patientData.firstName || '').trim(),
            lastName: String(patientData.lastName || '').trim(),
            status: normalizeStatus(patientData.status || 'active'),
        };

        patientIdInput.value = originalPatient.id;
        firstNameInput.value = originalPatient.firstName;
        lastNameInput.value = originalPatient.lastName;
        statusSelect.value = originalPatient.status;
        resetPasswordField();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        patientIdInput.value = '';
        resetPasswordField();
        hideModalError();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function buildPayload() {
        var firstName = firstNameInput.value.trim();
        var lastName = lastNameInput.value.trim();
        var status = normalizeStatus(statusSelect.value);
        var newPassword = newPasswordInput.value.trim();

        return {
            firstName: firstName,
            lastName: lastName,
            status: status,
            newPassword: newPassword,
            changed: firstName !== originalPatient.firstName || lastName !== originalPatient.lastName || status !== originalPatient.status || newPassword !== '',
        };
    }

    async function updatePatient(patientId, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var requestBody = {
            first_name: payload.firstName,
            last_name: payload.lastName,
            status: payload.status,
        };

        if (payload.newPassword !== '') {
            requestBody.new_password = payload.newPassword;
        }

        var response = await fetch('/api/v1/patients/' + encodeURIComponent(patientId), {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
            body: JSON.stringify(requestBody),
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.error || data.message || 'No se pudo actualizar el paciente.');
        }
    }

    table.addEventListener('click', function (event) {
        var editButton = event.target.closest('[data-patient-edit]');

        if (!editButton || editButton.disabled) {
            return;
        }

        openModal({
            id: editButton.getAttribute('data-patient-id') || '',
            firstName: editButton.getAttribute('data-patient-first-name') || '',
            lastName: editButton.getAttribute('data-patient-last-name') || '',
            status: editButton.getAttribute('data-patient-status') || 'active',
        });
    });

    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var patientId = patientIdInput.value.trim();

        if (!patientId) {
            showModalError('No se encontro el ID del paciente a editar.');
            return;
        }

        var payload = buildPayload();

        if (!payload.changed) {
            closeModal();
            return;
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await updatePatient(patientId, payload);
            setSubmittingState(false);
            closeModal();

            if (window.patientsPage && typeof window.patientsPage.hideError === 'function') {
                window.patientsPage.hideError();
            }

            if (window.patientsPage && typeof window.patientsPage.reload === 'function') {
                await window.patientsPage.reload();
            }
        } catch (error) {
            closeModal();

            if (window.patientsPage && typeof window.patientsPage.showError === 'function') {
                window.patientsPage.showError(error.message || 'No se pudo actualizar el paciente.');
            }
        } finally {
            if (isSubmitting) {
                setSubmittingState(false);
            }
        }
    });
})();