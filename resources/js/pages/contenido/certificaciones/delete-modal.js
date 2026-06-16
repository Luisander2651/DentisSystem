export function initCertificationsDeleteModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-certifications-delete-modal]');
    var form = modal ? modal.querySelector('[data-certifications-delete-form]') : null;
    var idInput = modal ? modal.querySelector('[data-certifications-delete-id]') : null;
    var nameLabel = modal ? modal.querySelector('[data-certifications-delete-name]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-certifications-delete-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-certifications-delete-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-certifications-delete-error]') : null;

    if (!modal || !form || !idInput || !nameLabel || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        nameLabel.textContent = data.name || 'esta certificacion';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        hideModalError();
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function deleteCertification(id) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/certifications/' + id, {
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
            throw new Error(data.error || data.message || 'No se pudo eliminar la certificacion.');
        }
    }

    root.addEventListener('click', function (event) {
        var target = event.target;
        if (target.hasAttribute('data-certifications-delete')) {
            openModal({
                id: target.getAttribute('data-certification-id'),
                name: target.getAttribute('data-certification-name')
            });
        }
    });

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var id = idInput.value;

        if (!id) {
            showModalError('No se pudo identificar el registro a eliminar.');
            return;
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await deleteCertification(id);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo eliminar la certificacion.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo eliminar la certificacion.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}