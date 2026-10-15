import { closeDialog, openDialog } from '../../ui/dialog';
import { announce, failureMessage } from '../../ui/status';

(function () {
    if (window.__usersDeleteInit) {
        return;
    }

    window.__usersDeleteInit = true;

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

    var table = document.getElementById('users-table');
    var modal = document.getElementById('users-delete-modal');

    if (!table || !modal) {
        return;
    }

    var confirmTarget = modal.querySelector('[data-confirm-delete-name]');
    var cancelButtons = modal.querySelectorAll('[data-confirm-delete-cancel]');
    var acceptButton = modal.querySelector('[data-confirm-delete-submit]');

    var currentUserId = null;
    var isDeleting = false;

    function openModal(userId, userLabel) {
        currentUserId = userId;

        if (confirmTarget) {
            confirmTarget.textContent = userLabel || 'este usuario';
        }

        openDialog(modal);
    }

    function closeModal() {
        currentUserId = null;
        closeDialog(modal);
    }

    async function deleteUserById(userId) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/users/' + encodeURIComponent(userId), {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
        });

        var payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            // BR-18: a missing user is now 404 where this endpoint used to answer 409,
            // and BR-21 answers 403 when an administrator tries to delete themselves.
            if (response.status === 404) {
                throw new Error('El usuario ya no existe. Actualiza la lista.');
            }

            throw new Error(failureMessage(response.status, payload));
        }
    }

    table.addEventListener('click', function (event) {
        var deleteButton = event.target.closest('[data-user-delete]');

        if (!deleteButton || deleteButton.disabled) {
            return;
        }

        var userId = deleteButton.getAttribute('data-user-id');
        var userLabel = deleteButton.getAttribute('data-user-first-name') + ' ' + deleteButton.getAttribute('data-user-last-name');

        if (!userId) {
            return;
        }

        openModal(userId, userLabel);
    });

    cancelButtons.forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    if (acceptButton) {
        acceptButton.addEventListener('click', async function () {
            if (!currentUserId || isDeleting) {
                return;
            }

            isDeleting = true;
            acceptButton.disabled = true;

            try {
                await deleteUserById(currentUserId);
                closeModal();
                announce('Usuario eliminado.');

                if (window.usersPage && typeof window.usersPage.hideError === 'function') {
                    window.usersPage.hideError();
                }

                if (window.usersPage && typeof window.usersPage.reload === 'function') {
                    await window.usersPage.reload();
                }
            } catch (error) {
                closeModal();

                if (window.usersPage && typeof window.usersPage.showError === 'function') {
                    window.usersPage.showError(error instanceof TypeError ? failureMessage(0) : error.message);
                }
            } finally {
                isDeleting = false;
                acceptButton.disabled = false;
            }
        });
    }
})();