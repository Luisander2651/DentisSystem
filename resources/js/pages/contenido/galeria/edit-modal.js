export function initGalleryEditModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-gallery-edit-modal]');
    var form = modal ? modal.querySelector('[data-gallery-edit-form]') : null;
    var idInput = modal ? modal.querySelector('[data-gallery-edit-id]') : null;
    var labelInput = modal ? modal.querySelector('[data-gallery-edit-label]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-gallery-edit-description]') : null;
    var statusSelect = modal ? modal.querySelector('[data-gallery-edit-status]') : null;
    var imageInput = modal ? modal.querySelector('[data-gallery-edit-image]') : null;
    var previewImage = modal ? modal.querySelector('[data-gallery-edit-preview]') : null;
    var previewPlaceholder = modal ? modal.querySelector('[data-gallery-edit-preview-empty]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-gallery-edit-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-gallery-edit-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-gallery-edit-error]') : null;

    if (!modal || !form || !idInput || !descriptionInput || !statusSelect || !imageInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
        return;
    }

    var isSubmitting = false;

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

    function setSubmittingState(submitting) {
        isSubmitting = submitting;
        submitButton.disabled = submitting;
        cancelButtons.forEach(function (button) {
            button.disabled = submitting;
        });
        imageInput.disabled = submitting;
        descriptionInput.disabled = submitting;
        statusSelect.disabled = submitting;
    }

    function togglePreview(url, label) {
        var hasUrl = Boolean(url);

        if (previewImage) {
            previewImage.src = hasUrl ? url : '';
            previewImage.alt = label || 'Imagen actual';
            previewImage.classList.toggle('hidden', !hasUrl);
        }

        if (previewPlaceholder) {
            previewPlaceholder.classList.toggle('hidden', hasUrl);
        }
    }

    function openModal(record) {
        hideModalError();

        idInput.value = record.id || '';
        if (labelInput) {
            labelInput.textContent = record.label || 'registro';
        }

        descriptionInput.value = record.description || '';
        statusSelect.value = record.status || 'visible';
        imageInput.value = '';
        togglePreview(record.url || '', record.label || 'Imagen actual');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        idInput.value = '';
        if (labelInput) {
            labelInput.textContent = 'registro';
        }
        togglePreview('', 'Imagen actual');
        hideModalError();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function updateGalleryImage(id, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/gallery-images/' + encodeURIComponent(id), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
            body: payload,
        });

        var data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(data.error || data.message || 'No se pudo actualizar la imagen de galeria.');
        }
    }

    root.addEventListener('click', function (event) {
        var editButton = event.target.closest('[data-gallery-edit]');

        if (!editButton || editButton.disabled) {
            return;
        }

        openModal({
            id: editButton.getAttribute('data-gallery-id') || '',
            label: editButton.getAttribute('data-gallery-label') || 'registro',
            description: editButton.getAttribute('data-gallery-description') || '',
            status: editButton.getAttribute('data-gallery-status') || 'visible',
            url: editButton.getAttribute('data-gallery-url') || '',
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

    imageInput.addEventListener('change', function () {
        var file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!file) {
            return;
        }

        var reader = new FileReader();
        reader.onload = function () {
            togglePreview(String(reader.result || ''), file.name);
        };
        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var id = idInput.value.trim();

        if (!id) {
            showModalError('No se encontro el ID de la imagen a editar.');
            return;
        }

        var description = descriptionInput.value.trim();
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
        var status = String(statusSelect.value || 'visible').trim();

        if (!description && !imageFile && !status) {
            showModalError('Debe cambiar la descripcion, la imagen o el estado.');
            return;
        }

        var payload = new FormData();
        payload.append('_method', 'PUT');
        payload.append('description', description);
        payload.append('status', status === 'hidden' ? 'hidden' : 'visible');

        if (imageFile) {
            payload.append('image', imageFile);
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await updateGalleryImage(id, payload);
            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo actualizar la imagen de galeria.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo actualizar la imagen de galeria.');
            }
        } finally {
            setSubmittingState(false);
            if (!errorBox.textContent) {
                closeModal();
            }
        }
    });
}