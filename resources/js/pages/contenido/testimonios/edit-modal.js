import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage } from '../../../ui/status';
export function initTestimonialsEditModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-testimonials-edit-modal]');
    var form = modal ? modal.querySelector('[data-testimonials-edit-form]') : null;
    var idInput = modal ? modal.querySelector('[data-testimonials-edit-id]') : null;
    var authorLabel = modal ? modal.querySelector('[data-testimonials-edit-author-label]') : null;
    var descriptionLabel = modal ? modal.querySelector('[data-testimonials-edit-description-label]') : null;
    var statusInput = modal ? modal.querySelector('[data-testimonials-edit-status]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-testimonials-edit-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-testimonials-edit-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-testimonials-edit-error]') : null;

    errorBox = errorBox || document.createElement('p');

    if (!modal || !form || !idInput || !authorLabel || !descriptionLabel || !statusInput || !cancelButtons.length || !submitButton || !api) {
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
        statusInput.disabled = submitting;
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        authorLabel.textContent = data.author || '';
        descriptionLabel.textContent = data.description || '';
        statusInput.value = data.status || 'visible';

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

    async function updateTestimonial(id, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/testimonials/' + id, {
            method: 'PUT',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
            body: JSON.stringify(payload),
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
        if (target.hasAttribute('data-testimonials-edit')) {
            openModal({
                id: target.getAttribute('data-testimonial-id'),
                author: target.getAttribute('data-testimonial-author'),
                description: target.getAttribute('data-testimonial-description'),
                status: target.getAttribute('data-testimonial-status')
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
        var author = authorLabel.textContent.trim();
        var description = descriptionLabel.textContent.trim();
        var status = statusInput.value;

        if (!id || !author || !description || !status) {
            showModalError('No pudimos identificar el testimonio. Cierra el diálogo e inténtalo de nuevo.');
            return;
        }

        var payload = {
            author: author,
            description: description,
            status: status
        };

        hideModalError();
        setSubmittingState(true);

        var saved = false;

        try {
            await updateTestimonial(id, payload);
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
                announce('Cambios guardados.');
            }
        }
    });
}