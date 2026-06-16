export function initCertificationsEditModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-certifications-edit-modal]');
    var form = modal ? modal.querySelector('[data-certifications-edit-form]') : null;
    var idInput = modal ? modal.querySelector('[data-certifications-edit-id]') : null;
    var nameInput = modal ? modal.querySelector('[data-certifications-edit-name]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-certifications-edit-description]') : null;
    var dateInput = modal ? modal.querySelector('[data-certifications-edit-date]') : null;
    var statusInput = modal ? modal.querySelector('[data-certifications-edit-status]') : null;
    var imageInput = modal ? modal.querySelector('[data-certifications-edit-image]') : null;
    var previewImage = modal ? modal.querySelector('[data-certifications-edit-preview]') : null;
    var previewPlaceholder = modal ? modal.querySelector('[data-certifications-edit-preview-empty]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-certifications-edit-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-certifications-edit-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-certifications-edit-error]') : null;

    if (!modal || !form || !idInput || !nameInput || !descriptionInput || !dateInput || !statusInput || !imageInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
        statusInput.disabled = submitting;
        imageInput.disabled = submitting;
    }

    function togglePreview(url, label) {
        var hasUrl = Boolean(url);

        if (previewImage) {
            previewImage.src = hasUrl ? url : '';
            previewImage.alt = label || 'Certificacion';
            previewImage.classList.toggle('hidden', !hasUrl);
        }

        if (previewPlaceholder) {
            previewPlaceholder.classList.toggle('hidden', hasUrl);
        }
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        nameInput.value = data.name || '';
        descriptionInput.value = data.description || '';
        dateInput.value = data.date || '';
        statusInput.value = data.status || 'visible';
        
        togglePreview(data.image, data.name);
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        togglePreview('', '');
        hideModalError();
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function updateCertification(id, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        // Note: Update Certification uses POST with _method=PUT to handle multipart/form-data
        payload.append('_method', 'PUT');

        var response = await fetch('/api/v1/certifications/' + id, {
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
            throw new Error(data.error || data.message || 'No se pudo actualizar la certificacion.');
        }
    }

    root.addEventListener('click', function (event) {
        var target = event.target;
        if (target.hasAttribute('data-certifications-edit')) {
            openModal({
                id: target.getAttribute('data-certification-id'),
                name: target.getAttribute('data-certification-name'),
                description: target.getAttribute('data-certification-description'),
                date: target.getAttribute('data-certification-date'),
                status: target.getAttribute('data-certification-status'),
                image: target.getAttribute('data-certification-image')
            });
        }
    });

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
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

        var id = idInput.value;
        var name = nameInput.value.trim();
        var description = descriptionInput.value.trim();
        var date = dateInput.value;
        var status = statusInput.value;
        var imageFile = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!id || !name || !description || !date || !status) {
            showModalError('Todos los campos son obligatorios.');
            return;
        }

        var payload = new FormData();
        payload.append('name', name);
        payload.append('description', description);
        payload.append('date', date);
        payload.append('status', status);
        if (imageFile) {
            payload.append('image', imageFile);
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await updateCertification(id, payload);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo actualizar la certificacion.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo actualizar la certificacion.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}