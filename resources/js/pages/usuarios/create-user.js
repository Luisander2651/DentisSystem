(function () {
    if (window.__usersCreateInit) {
        return;
    }

    window.__usersCreateInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    var openButton = document.querySelector('[data-create-user-open]');
    var modal = document.getElementById('users-create-modal');

    if (!openButton || !modal) {
        return;
    }

    var form = modal.querySelector('[data-create-user-form]');
    var firstNameInput = modal.querySelector('[data-create-user-first-name]');
    var lastNameInput = modal.querySelector('[data-create-user-last-name]');
    var emailInput = modal.querySelector('[data-create-user-email]');
    var passwordInput = modal.querySelector('[data-create-user-password]');
    var roleSelect = modal.querySelector('[data-create-user-role]');
    var cancelButton = modal.querySelector('[data-create-user-cancel]');
    var submitButton = modal.querySelector('[data-create-user-submit]');
    var errorBox = modal.querySelector('[data-create-user-error]');

    if (!form || !firstNameInput || !lastNameInput || !emailInput || !passwordInput || !roleSelect || !cancelButton || !submitButton || !errorBox) {
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

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        submitButton.disabled = submitting;
        cancelButton.disabled = submitting;
    }

    function resetPasswordField() {
        var passwordButton = modal.querySelector('[data-toggle-password][data-target-input="users-create-password"]');
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

    function openModal() {
        hideModalError();
        form.reset();
        resetPasswordField();
        roleSelect.value = 'admin';

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
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function createUser(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/users', {
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
            throw new Error(data.error || data.message || 'No se pudo crear el usuario.');
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

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var payload = {
            first_name: firstNameInput.value.trim(),
            last_name: lastNameInput.value.trim(),
            email: emailInput.value.trim(),
            password: passwordInput.value.trim(),
            role_id: roleSelect.value.trim(),
        };

        hideModalError();
        setSubmittingState(true);

        try {
            await createUser(payload);
            setSubmittingState(false);
            closeModal();

            if (window.usersPage && typeof window.usersPage.hideError === 'function') {
                window.usersPage.hideError();
            }

            if (window.usersPage && typeof window.usersPage.reload === 'function') {
                await window.usersPage.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo crear el usuario.');
        } finally {
            if (isSubmitting) {
                setSubmittingState(false);
            }
        }
    });
})();
