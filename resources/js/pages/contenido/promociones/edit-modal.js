import { closeDialog, openDialog } from '../../../ui/dialog';
import { announce, announceError, failureMessage, markFieldErrors } from '../../../ui/status';
export function initPromotionsEditModal(context) {
    var root = context && context.root ? context.root : document;
    var api = context && context.api ? context.api : null;
    var modal = root.querySelector('[data-promotions-edit-modal]');
    var form = modal ? modal.querySelector('[data-promotions-edit-form]') : null;
    var idInput = modal ? modal.querySelector('[data-promotions-edit-id]') : null;
    var nameInput = modal ? modal.querySelector('[data-promotions-edit-name]') : null;
    var descriptionInput = modal ? modal.querySelector('[data-promotions-edit-description]') : null;
    var discountInput = modal ? modal.querySelector('[data-promotions-edit-discount]') : null;
    var statusInput = modal ? modal.querySelector('[data-promotions-edit-status]') : null;
    var startDateInput = modal ? modal.querySelector('[data-promotions-edit-start-date]') : null;
    var endDateInput = modal ? modal.querySelector('[data-promotions-edit-end-date]') : null;
    var cancelButtons = modal ? modal.querySelectorAll('[data-promotions-edit-cancel]') : [];
    var submitButton = modal ? modal.querySelector('[data-promotions-edit-submit]') : null;
    var errorBox = modal ? modal.querySelector('[data-promotions-edit-error]') : null;

    errorBox = errorBox || document.createElement('p');

    if (!modal || !form || !idInput || !nameInput || !descriptionInput || !statusInput || !startDateInput || !endDateInput || !cancelButtons.length || !submitButton || !api) {
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
        statusInput.disabled = submitting;
        startDateInput.disabled = submitting;
        endDateInput.disabled = submitting;
    }

    function openModal(data) {
        hideModalError();
        idInput.value = data.id || '';
        nameInput.value = data.name || '';
        descriptionInput.value = data.description || '';
        discountInput.value = data.discount || '';
        statusInput.value = data.status || 'visible';
        startDateInput.value = data.startDate || '';
        endDateInput.value = data.endDate || '';

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

    async function updatePromotion(id, payload) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/promotions/' + id, {
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
        if (target.hasAttribute('data-promotions-edit')) {
            openModal({
                id: target.getAttribute('data-promotion-id'),
                name: target.getAttribute('data-promotion-name'),
                description: target.getAttribute('data-promotion-description'),
                discount: target.getAttribute('data-promotion-discount'),
                status: target.getAttribute('data-promotion-status'),
                startDate: target.getAttribute('data-promotion-start-date'),
                endDate: target.getAttribute('data-promotion-end-date')
            });
        }
    });

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

        var id = idInput.value;
        var name = nameInput.value.trim();
        var description = descriptionInput.value.trim();
        var discount = discountInput.value ? parseFloat(discountInput.value) : null;
        var status = statusInput.value;
        var startDate = startDateInput.value;
        var endDate = endDateInput.value;

        if (markMissing([nameInput, descriptionInput, startDateInput, endDateInput]) || !id) {
            showModalError('Completa todos los campos obligatorios.');
            return;
        }

        var payload = {
            name: name,
            description: description,
            discount_percentage: discount,
            status: status,
            start_date: startDate,
            end_date: endDate
        };

        hideModalError();
        setSubmittingState(true);

        var saved = false;

        try {
            await updatePromotion(id, payload);
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