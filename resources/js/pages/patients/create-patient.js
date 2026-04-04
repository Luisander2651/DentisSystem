(function () {
    if (window.__patientsCreateInit) {
        return;
    }

    window.__patientsCreateInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    var openButton = document.querySelector('[data-create-patient-open]');
    var modal = document.getElementById('patients-create-modal');

    if (!openButton || !modal) {
        return;
    }

    var form = modal.querySelector('[data-create-patient-form]');
    var firstNameInput = modal.querySelector('[data-create-patient-first-name]');
    var lastNameInput = modal.querySelector('[data-create-patient-last-name]');
    var emailInput = modal.querySelector('[data-create-patient-email]');
    var passwordInput = modal.querySelector('[data-create-patient-password]');
    var cancelButton = modal.querySelector('[data-create-patient-cancel]');
    var submitButton = modal.querySelector('[data-create-patient-submit]');
    var errorBox = modal.querySelector('[data-create-patient-error]');

    if (!form || !firstNameInput || !lastNameInput || !emailInput || !passwordInput || !cancelButton || !submitButton || !errorBox) {
        return;
    }

    var isSubmitting = false;

    function showModalError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function hideModalError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function resetPasswordField() {
        var passwordButton = modal.querySelector('[data-toggle-password][data-target-input="patients-create-password"]');
        var openIcon = passwordButton ? passwordButton.querySelector('[data-eye-open]') : null;
        var closeIcon = passwordButton ? passwordButton.querySelector('[data-eye-closed]') : null;

        passwordInput.value = '';
        passwordInput.type = 'password';

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

    function isFormValid() {
        return firstNameInput.value.trim() !== ''
            && lastNameInput.value.trim() !== ''
            && emailInput.value.trim() !== ''
            && emailInput.checkValidity()
            && passwordInput.value.trim() !== '';
    }

    function updateSubmitState() {
        submitButton.disabled = !isFormValid();
    }

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        submitButton.disabled = submitting || !isFormValid();
        cancelButton.disabled = submitting;
    }

    function openModal() {
        hideModalError();
        form.reset();
        resetPasswordField();
        updateSubmitState();

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        resetPasswordField();
        hideModalError();
        updateSubmitState();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function createPatient(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/patients', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
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
            throw new Error(data.error || data.message || 'No se pudo crear el paciente.');
        }
    }

    openButton.addEventListener('click', function () {
        openModal();
    });

    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    [firstNameInput, lastNameInput, emailInput, passwordInput].forEach(function (input) {
        input.addEventListener('input', updateSubmitState);
        input.addEventListener('blur', updateSubmitState);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting || !isFormValid()) {
            return;
        }

        var payload = {
            first_name: firstNameInput.value.trim(),
            last_name: lastNameInput.value.trim(),
            email: emailInput.value.trim(),
            password: passwordInput.value.trim(),
        };

        hideModalError();
        setSubmittingState(true);

        try {
            await createPatient(payload);
            setSubmittingState(false);
            closeModal();

            if (window.patientsPage && typeof window.patientsPage.hideError === 'function') {
                window.patientsPage.hideError();
            }

            if (window.patientsPage && typeof window.patientsPage.reload === 'function') {
                await window.patientsPage.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo crear el paciente.');
        } finally {
            if (isSubmitting) {
                setSubmittingState(false);
            }
        }
    });

    updateSubmitState();
})();