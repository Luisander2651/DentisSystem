export function initTestimonialsDeleteModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-testimonials-delete-modal]');
    var form = modal ? modal.querySelector('[data-testimonials-delete-form]') : null;
    var idInput = modal ? modal.querySelector('[data-testimonials-delete-id]') : null;
    var targetLabel = modal ? modal.querySelector('[data-testimonials-delete-target]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-testimonials-delete-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-testimonials-delete-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-testimonials-delete-error]') : null;

    if (!modal || !form || !idInput || !targetLabel || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
        targetLabel.textContent = data.author || 'el autor';
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

    async function deleteTestimonial(id) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/testimonials/' + id, {
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
            throw new Error(data.error || data.message || 'No se pudo eliminar el testimonio.');
        }
    }

    root.addEventListener('click', function (event) {
        var target = event.target;
        if (target.hasAttribute('data-testimonials-delete')) {
            openModal({
                id: target.getAttribute('data-testimonial-id'),
                author: target.getAttribute('data-testimonial-author')
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

        if (!id) {
            showModalError('No se pudo identificar el registro a eliminar.');
            return;
        }

        hideModalError();
        setSubmittingState(true);

        try {
            await deleteTestimonial(id);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo eliminar el testimonio.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo eliminar el testimonio.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}