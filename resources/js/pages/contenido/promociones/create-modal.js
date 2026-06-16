export function initPromotionsCreateModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var openButton = root.querySelector('[data-promotions-create-open]');
    var modal = root.querySelector('[data-promotions-create-modal]');
    var form = modal ? modal.querySelector('[data-promotions-create-form]') : null;
    var nameInput = modal ? modal.querySelector('[data-promotions-create-name]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-promotions-create-description]') : null;
    var discountInput = modal ? modal.querySelector('[data-promotions-create-discount]') : null;
    var startDateInput = modal ? modal.querySelector('[data-promotions-create-start-date]') : null;
    var endDateInput = modal ? modal.querySelector('[data-promotions-create-end-date]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-promotions-create-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-promotions-create-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-promotions-create-error]') : null;

    if (!openButton || !modal || !form || !nameInput || !descriptionInput || !startDateInput || !endDateInput || !cancelButtons.length || !submitButton || !errorBox || !api) {
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
        discountInput.disabled = submitting;
        startDateInput.disabled = submitting;
        endDateInput.disabled = submitting;
        openButton.disabled = submitting;
    }

    function openModal() {
        hideModalError();
        form.reset();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        window.setTimeout(function () {
            nameInput.focus();
        }, 0);
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

    async function createPromotion(payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/promotions', {
            method: 'POST',
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
            throw new Error(data.error || data.message || 'No se pudo crear la promocion.');
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

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        var name = nameInput.value.trim();
        var description = descriptionInput.value.trim();
        var discount = discountInput.value ? parseFloat(discountInput.value) : null;
        var startDate = startDateInput.value;
        var endDate = endDateInput.value;

        if (!name || !description || !startDate || !endDate) {
            showModalError('Todos los campos obligatorios deben estar llenos.');
            return;
        }

        var payload = {
            name: name,
            description: description,
            discount_percentage: discount,
            start_date: startDate,
            end_date: endDate,
            status: 'visible'
        };

        hideModalError();
        setSubmittingState(true);

        try {
            await createPromotion(payload);

            if (typeof api.hideError === 'function') {
                api.hideError();
            }

            if (typeof api.reload === 'function') {
                await api.reload();
            }
        } catch (error) {
            showModalError(error.message || 'No se pudo crear la promocion.');

            if (typeof api.showError === 'function') {
                api.showError(error.message || 'No se pudo crear la promocion.');
            }
        } finally {
            setSubmittingState(false);
            closeModal();
        }
    });
}