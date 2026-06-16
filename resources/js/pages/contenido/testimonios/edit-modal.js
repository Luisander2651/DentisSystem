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

    if (!modal || !form || !idInput || !authorLabel || !descriptionLabel || !statusInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
        statusInput.disabled = submitting;
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        authorLabel.textContent = data.author || '';
        descriptionLabel.textContent = data.description || '';
        statusInput.value = data.status || 'visible';

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (isSubmitting) {
            return;
        }

        form.reset();
        hideModalError();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
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
            throw new Error(data.error || data.message || 'No se pudo actualizar el testimonio.');
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

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
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
            showModalError('No se pudo identificar la información necesaria para actualizar.');
            return;
        }

        var payload = {
            author: author,
            description: description,
            status: status
        };

        hideModalError();
        setSubmittingState(true);

        try {
            await updateTestimonial(id, payload);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo actualizar el testimonio.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo actualizar el testimonio.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}