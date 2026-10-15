import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage, markFieldErrors } from '../../../ui/status';
export function initCertificationsCreateModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var openButton = root.querySelector('[data-certifications-create-open]');
    var modal = root.querySelector('[data-certifications-create-modal]');
    var form = modal ? modal.querySelector('[data-certifications-create-form]') : null;
    var nameInput = modal ? modal.querySelector('[data-certifications-create-name]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-certifications-create-description]') : null;
    var dateInput = modal ? modal.querySelector('[data-certifications-create-date]') : null;
    var imageInput = modal ? modal.querySelector('[data-certifications-create-image]') : null;
    var previewImage = modal ? modal.querySelector('[data-certifications-create-preview]') : null;
    var previewPlaceholder = modal ? modal.querySelector('[data-certifications-create-preview-empty]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-certifications-create-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-certifications-create-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-certifications-create-error]') : null;

    errorBox = errorBox || document.createElement('p');

    if (!openButton || !modal || !form || !nameInput || !descriptionInput || !dateInput || !imageInput || !cancelButtons.length || !submitButton || !api) {
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
        nameInput.disabled = submitting;
        descriptionInput.disabled = submitting;
        dateInput.disabled = submitting;
        imageInput.disabled = submitting;
        openButton.disabled = submitting;
    }

    function togglePreview(url, label) {
        var hasUrl = Boolean(url);

        if (previewImage) {
            previewImage.src = hasUrl ? url : '';
            previewImage.alt = label || 'Nueva certificación';
            previewImage.classList.toggle('hidden', !hasUrl);
        }

        if (previewPlaceholder) {
            previewPlaceholder.classList.toggle('hidden', hasUrl);
        }
    }

    function openModal() {
        hideModalError();
        form.reset();
        togglePreview('', 'Nueva certificación');
        openDialog(modal);
        window.setTimeout(function () {
            nameInput.focus();
        }, 0);
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        togglePreview('', 'Nueva certificación');
        hideModalError();
        closeDialog(modal);
    }

    async function createCertification(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/certifications', {
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
            togglePreview('', 'Nueva certificación');
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

        var name = nameInput.value.trim();
        var description = descriptionInput.value.trim();
        var date = dateInput.value;
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (markMissing([nameInput, descriptionInput, dateInput, imageInput])) {
            showModalError('Completa todos los campos obligatorios.');
            return;
        }

        var payload = new FormData();
        payload.append('name', name);
        payload.append('description', description);
        payload.append('date', date);
        payload.append('image', imageFile);

        hideModalError();
        setSubmittingState(true);

        var saved = false;

        try {
            await createCertification(payload);
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
                announce('Certificación creada.');
            }
        }
    });
}