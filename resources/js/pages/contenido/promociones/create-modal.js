import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage, markFieldErrors } from '../../../ui/status';
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

    errorBox = errorBox || document.createElement('p');

    if (!openButton || !modal || !form || !nameInput || !descriptionInput || !startDateInput || !endDateInput || !cancelButtons.length || !submitButton || !api) {
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
        discountInput.disabled = submitting;
        startDateInput.disabled = submitting;
        endDateInput.disabled = submitting;
        openButton.disabled = submitting;
    }

    function openModal() {
        hideModalError();
        form.reset();
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
        hideModalError();
        closeDialog(modal);
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
            throw new Error(failureMessage(response.status, data));
        }
    }

    openButton.addEventListener('click', openModal);

    cancelButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
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
        var discount = discountInput.value ? parseFloat(discountInput.value) : null;
        var startDate = startDateInput.value;
        var endDate = endDateInput.value;

        if (markMissing([nameInput, descriptionInput, startDateInput, endDateInput])) {
            showModalError('Completa todos los campos obligatorios.');
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

        var saved = false;

        try {
            await createPromotion(payload);
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
                announce('Promoción creada.');
            }
        }
    });
}