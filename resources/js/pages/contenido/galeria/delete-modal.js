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

    if (!modal || !form || !idInput || !cancelButtons.length || !acceptButton || !errorBox || !api) {
        return;
    }

    var isDeleting = false;

    function getCookie(name) {
        return typeof api.getCookie === 'function' ? api.getCookie(name) : null;
    }

    function showModalError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
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

        modal.classList.remove('hidden');
        modal.classList.add('flex');
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
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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
            throw new Error(data.error || data.message || 'No se pudo eliminar la imagen de galeria.');
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

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isDeleting) {
            return;
        }

        var id = idInput.value.trim();

        if (!id) {
            showModalError('No se encontro el ID de la imagen a eliminar.');
            return;
        }

        hideModalError();
        isDeleting = true;
        acceptButton.disabled = true;
        cancelButtons.forEach(function (button) {
            button.disabled = true;
        });

        try {
            await deleteGalleryImage(id);
            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo eliminar la imagen de galeria.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo eliminar la imagen de galeria.');
            }
        } finally {
            isDeleting = false;
            acceptButton.disabled = false;
            cancelButtons.forEach(function (button) {
                button.disabled = false;
            });
            if (!errorBox.textContent) {
                closeModal();
            }
        }
    });
}