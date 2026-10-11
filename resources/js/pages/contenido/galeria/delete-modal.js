import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage } from '../../../ui/status';
export function initGalleryDeleteModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-gallery-delete-modal]');
    var form = modal ? modal.querySelector('[data-gallery-delete-form]') : null;
    var idInput = modal ? modal.querySelector('[data-gallery-delete-id]') : null;
    var labelTarget = modal ? modal.querySelector('[data-gallery-delete-target]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-gallery-delete-cancel]') : [];
    var acceptButton = modal ? modal.querySelector('[data-gallery-delete-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-gallery-delete-error]') : null;

    errorBox = errorBox || document.createElement('p');

    if (!modal || !form || !idInput || !cancelButtons.length || !acceptButton || !api) {
        return;
    }

    var isDeleting = false;

    function getCookie(name) {
        return typeof api.getCookie === 'function' ? api.getCookie(name) : null;
    }

    function showModalError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
        announceError(message);
    }

    function hideModalError() {
        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function openModal(record) {
        hideModalError();
        idInput.value = record.id || '';

        if (labelTarget) {
            labelTarget.textContent = record.label || 'este registro';
        }

        openDialog(modal);
    }

    function closeModal() {
        if (isDeleting) {
            return;
        }

        form.reset();
        idInput.value = '';

        if (labelTarget) {
            labelTarget.textContent = 'este registro';
        }

        hideModalError();
        closeDialog(modal);
    }

    async function deleteGalleryImage(id) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/gallery-images/' + encodeURIComponent(id), {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(failureMessage(response.status, data));
        }
    }

    root.addEventListener('click', function (event) {
        var deleteButton = event.target.closest('[data-gallery-delete]');

        if (!deleteButton || deleteButton.disabled) {
            return;
        }

        openModal({
            id: deleteButton.getAttribute('data-gallery-id') || '',
            label: deleteButton.getAttribute('data-gallery-label') || 'este registro',
        });
    });

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });


    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isDeleting) {
            return;
        }

        var id = idInput.value.trim();

        if (!id) {
            showModalError('No pudimos identificar la imagen. Cierra el diálogo e inténtalo de nuevo.');
            return;
        }

        hideModalError();
        isDeleting = true;
        acceptButton.disabled = true;
        cancelButtons.forEach(function (button) {
            button.disabled = true;
        });

        var saved = false;

        try {
            await deleteGalleryImage(id);
            saved = true;
            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error instanceof TypeError ? failureMessage(0) : error.message);
        } finally {
            isDeleting = false;
            acceptButton.disabled = false;
            cancelButtons.forEach(function (button) {
                button.disabled = false;
            });
            if (saved) {
                closeModal();
                announce('Imagen eliminada.');
            }
        }
    });
}