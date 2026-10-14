import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage, markFieldErrors } from '../../../ui/status';
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

    errorBox = errorBox || document.createElement('p');

    if (!openButton || !modal || !form || !descriptionInput || !imageInput || !cancelButtons.length || !submitButton || !api) {
        return;
    }

    var isSubmitting = false;

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
        openDialog(modal);
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
        closeDialog(modal);
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
            throw new Error(failureMessage(response.status, data));
        }
    }

    openButton.addEventListener('click', openModal);

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
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

    // Cada campo obligatorio vacío queda marcado con su error, ligado a él, además del aviso
    // general (spec 016, CA37).
    function markMissing(fields) {
        var missing = {};

        fields.forEach(function (field) {
            var empty = field.type === 'file' ? !(field.files && field.files[0]) : String(field.value).trim() === '';

            if (empty) {
                missing[field.name] = ['Este campo es obligatorio.'];
            }
        });

        markFieldErrors(form, missing);

        return Object.keys(missing).length > 0;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var description = descriptionInput.value.trim();
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (markMissing([descriptionInput, imageInput])) {
            showModalError(!description ? 'La descripción es obligatoria.' : 'La imagen es obligatoria.');
            return;
        }

        var payload = new FormData();
        payload.append('description', description);
        payload.append('image', imageFile);

        hideModalError();
        setSubmittingState(true);

        var saved = false;

        try {
            await createGalleryImage(payload);
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
            setSubmittingState(false);
            if (saved) {
                closeModal();
                announce('Imagen agregada.');
            }
        }
    });
}