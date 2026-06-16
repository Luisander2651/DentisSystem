(function () {
    if (window.__usersDeleteInit) {
        return;
    }

    window.__usersDeleteInit = true;

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

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        currentUserId = null;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
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
            throw new Error(payload.error || payload.message || 'No se pudo eliminar el usuario.');
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

                if (window.usersPage && typeof window.usersPage.hideError === 'function') {
                    window.usersPage.hideError();
                }

                if (window.usersPage && typeof window.usersPage.reload === 'function') {
                    await window.usersPage.reload();
                }
            } catch (error) {
                closeModal();

                if (window.usersPage && typeof window.usersPage.showError === 'function') {
                    window.usersPage.showError(error.message || 'No se pudo eliminar el usuario.');
                }
            } finally {
                isDeleting = false;
                acceptButton.disabled = false;
            }
        });
    }
})();