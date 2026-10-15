import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage } from '../../../ui/status';
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

    errorBox = errorBox || document.createElement('p');

    if (!modal || !form || !idInput || !nameLabel || !cancelButtons.length || !submitButton || !api) {
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
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        nameLabel.textContent = data.name || 'esta certificación';
        openDialog(modal);
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        hideModalError();
        closeDialog(modal);
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
            throw new Error(failureMessage(response.status, data));
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
            showModalError('No pudimos identificar la certificación. Cierra el diálogo e inténtalo de nuevo.');
            return;
        }

        hideModalError();
        setSubmittingState(true);

        var saved = false;

        try {
            await deleteCertification(id);
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
                announce('Certificación eliminada.');
            }
        }
    });
}