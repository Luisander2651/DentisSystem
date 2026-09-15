(function () {
    if (window.__usersEditInit) {
        return;
    }

    window.__usersEditInit = true;

    // The API answers 422 with Laravel's validation shape ({message, errors:{field:[...]}})
    // since BR-12, and 4xx business errors with {error}. Reading only `error`/`message`
    // would surface the generic "The given data was invalid." instead of the actual reason.
    function extractErrorMessage(data, fallback) {
        if (data && data.errors) {
            var fields = Object.keys(data.errors);

            if (fields.length && Array.isArray(data.errors[fields[0]]) && data.errors[fields[0]].length) {
                return data.errors[fields[0]][0];
            }
        }

        return (data && (data.error || data.message)) || fallback;
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    // BR-16: one vocabulary, the one the roles table stores, so no translation is needed.
    var KNOWN_ROLES = ['Administrador', 'Asistente', 'Doctor'];

    function isKnownRole(roleId) {
        return KNOWN_ROLES.indexOf(roleId) !== -1;
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

    var table = document.getElementById('users-table');
    var modal = document.getElementById('users-edit-modal');

    if (!table || !modal) {
        return;
    }

    var form = modal.querySelector('[data-edit-user-form]');
    var userIdInput = modal.querySelector('[data-edit-user-id]');
    var firstNameInput = modal.querySelector('[data-edit-user-first-name]');
    var lastNameInput = modal.querySelector('[data-edit-user-last-name]');
    var newPasswordInput = modal.querySelector('[data-edit-user-new-password]');
    var roleSelect = modal.querySelector('[data-edit-user-role]');
    var statusSelect = modal.querySelector('[data-edit-user-status]');
    var cancelButtons = modal.querySelectorAll('[data-edit-user-cancel]');
    var submitButton = modal.querySelector('[data-edit-user-submit]');
    var errorBox = modal.querySelector('[data-edit-user-error]');

    if (!form || !userIdInput || !firstNameInput || !lastNameInput || !newPasswordInput || !roleSelect || !statusSelect || !cancelButtons.length || !submitButton || !errorBox) {
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
        cancelButtons.forEach(function(btn) { btn.disabled = submitting; });
    }

    function resetPasswordField() {
        var passwordButton = modal.querySelector('[data-toggle-password][data-target-input="users-edit-new-password"]');
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

    function openModal(userData) {
        hideModalError();

        userIdInput.value = userData.id || '';
        firstNameInput.value = userData.firstName || '';
        lastNameInput.value = userData.lastName || '';
        resetPasswordField();
        // BR-28: an unrecognised role is surfaced, never silently rewritten. The previous
        // fall-through to 'asistent' meant opening and saving a Doctor demoted them.
        if (isKnownRole(userData.roleId)) {
            roleSelect.value = userData.roleId;
        } else {
            roleSelect.selectedIndex = -1;
            showModalError('El rol "' + (userData.roleId || 'desconocido') + '" no es valido. Selecciona uno antes de guardar.');
        }

        statusSelect.value = normalizeStatus(userData.status || 'active');

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        userIdInput.value = '';
        resetPasswordField();
        hideModalError();
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function updateUser(userId, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/users/' + encodeURIComponent(userId), {
            method: 'PUT',
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
            // BR-18: a missing user is now 404 where this endpoint used to answer 409.
            if (response.status === 404) {
                throw new Error('El usuario ya no existe. Actualiza la lista.');
            }

            throw new Error(extractErrorMessage(data, 'No se pudo actualizar el usuario.'));
        }
    }

    table.addEventListener('click', function (event) {
        var editButton = event.target.closest('[data-user-edit]');

        if (!editButton || editButton.disabled) {
            return;
        }

        openModal({
            id: editButton.getAttribute('data-user-id') || '',
            firstName: editButton.getAttribute('data-user-first-name') || '',
            lastName: editButton.getAttribute('data-user-last-name') || '',
            roleId: editButton.getAttribute('data-user-role-id') || '',
            status: editButton.getAttribute('data-user-status') || '',
        });
    });

    cancelButtons.forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var userId = userIdInput.value.trim();

        if (!userId) {
            showModalError('No se encontro el ID del usuario a editar.');
            return;
        }

        var payload = {
            first_name: firstNameInput.value.trim(),
            last_name: lastNameInput.value.trim(),
            role_id: roleSelect.value,
            status: normalizeStatus(statusSelect.value),
            new_password: newPasswordInput.value.trim(),
        };

        hideModalError();
        setSubmittingState(true);

        try {
            await updateUser(userId, payload);
            setSubmittingState(false);
            closeModal();

            if (window.usersPage && typeof window.usersPage.hideError === 'function') {
                window.usersPage.hideError();
            }

            if (window.usersPage && typeof window.usersPage.reload === 'function') {
                await window.usersPage.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo actualizar el usuario.');

            if (window.usersPage && typeof window.usersPage.showError === 'function') {
                window.usersPage.showError(error.message || 'No se pudo actualizar el usuario.');
            }
        } finally {
            if (isSubmitting) {
                setSubmittingState(false);
            }
        }
    });
})();
