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

    if (!openButton || !modal || !form || !nameInput || !descriptionInput || !dateInput || !imageInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
            previewImage.alt = label || 'Nueva certificacion';
            previewImage.classList.toggle('hidden', !hasUrl);
        }

        if (previewPlaceholder) {
            previewPlaceholder.classList.toggle('hidden', hasUrl);
        }
    }

    function openModal() {
        hideModalError();
        form.reset();
        togglePreview('', 'Nueva certificacion');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        window.setTimeout(function () {
            nameInput.focus();
        }, 0);
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        togglePreview('', 'Nueva certificacion');
        hideModalError();
        modal.classList.add('hidden');
        document.body.style.overflow = '';
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
            throw new Error(data.error || data.message || 'No se pudo crear la certificacion.');
        }
    }

    openButton.addEventListener('click', openModal);

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    imageInput.addEventListener('change', function () {
        var file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!file) {
            togglePreview('', 'Nueva certificacion');
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

        var name = nameInput.value.trim();
        var description = descriptionInput.value.trim();
        var date = dateInput.value;
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!name || !description || !date || !imageFile) {
            showModalError('Todos los campos son obligatorios.');
            return;
        }

        var payload = new FormData();
        payload.append('name', name);
        payload.append('description', description);
        payload.append('date', date);
        payload.append('image', imageFile);

        hideModalError();
        setSubmittingState(true);

        try {
            await createCertification(payload);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo crear la certificacion.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo crear la certificacion.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}