export function initGalleryCreateModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var openButton = root.querySelector('[data-gallery-create-open]');
    var modal = root.querySelector('[data-gallery-create-modal]');
    var form = modal ? modal.querySelector('[data-gallery-create-form]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-gallery-create-description]') : null;
    var imageInput = modal ? modal.querySelector('[data-gallery-create-image]') : null;
    var previewImage = modal ? modal.querySelector('[data-gallery-create-preview]') : null;
    var previewPlaceholder = modal ? modal.querySelector('[data-gallery-create-preview-empty]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-gallery-create-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-gallery-create-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-gallery-create-error]') : null;

    if (!openButton || !modal || !form || !descriptionInput || !imageInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
        openButton.disabled = submitting;
    }

    function togglePreview(url, label) {
        var hasUrl = Boolean(url);

        if (previewImage) {
            previewImage.src = hasUrl ? url : '';
            previewImage.alt = label || 'Nueva imagen';
            previewImage.classList.toggle('hidden', !hasUrl);
        }

        if (previewPlaceholder) {
            previewPlaceholder.classList.toggle('hidden', hasUrl);
        }
    }

    function openModal() {
        hideModalError();
        form.reset();
        togglePreview('', 'Nueva imagen');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.setTimeout(function () {
            descriptionInput.focus();
        }, 0);
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        togglePreview('', 'Nueva imagen');
        hideModalError();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function createGalleryImage(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/gallery-images', {
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
            throw new Error(data.error || data.message || 'No se pudo crear la imagen de galeria.');
        }
    }

    openButton.addEventListener('click', openModal);

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
            togglePreview('', 'Nueva imagen');
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

        var description = descriptionInput.value.trim();
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!description) {
            showModalError('La descripcion es obligatoria.');
            return;
        }

        if (!imageFile) {
            showModalError('La imagen es obligatoria.');
            return;
        }

        var payload = new FormData();
        payload.append('description', description);
        payload.append('image', imageFile);

        hideModalError();
        setSubmittingState(true);

        try {
            await createGalleryImage(payload);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo crear la imagen de galeria.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo crear la imagen de galeria.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}