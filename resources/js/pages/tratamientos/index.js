import { closeDialog, openDialog } from '../../ui/dialog';
import { announce, announceError, clearAnnouncements, failureMessage, markFieldErrors } from '../../ui/status';

(function () {
    if (window.__treatmentsIndexInit) {
        return;
    }

    window.__treatmentsIndexInit = true;

    var listEl = document.getElementById('treatments-list');
    var errorBox = document.getElementById('treatments-error');
    var loadingBox = document.getElementById('treatments-loading');
    var emptyBox = document.getElementById('treatments-empty');
    var searchInput = document.getElementById('treatments-search');

    var totalCountEl = document.querySelector('[data-treatments-total-count]');
    var withTimeCountEl = document.querySelector('[data-treatments-with-time-count]');
    var averageTimeEl = document.querySelector('[data-treatments-average-time]');

    var createButton = document.querySelector('[data-treatments-create-open]');

    var createModal = document.getElementById('treatments-create-modal');
    var createForm = createModal ? createModal.querySelector('[data-treatments-create-form]') : null;
    var createName = createModal ? createModal.querySelector('[data-treatments-create-name]') : null;
    var createDescription = createModal ? createModal.querySelector('[data-treatments-create-description]') : null;
    var createTime = createModal ? createModal.querySelector('[data-treatments-create-time]') : null;
    var createSubmit = createModal ? createModal.querySelector('[data-treatments-create-submit]') : null;
    var createCloseButtons = createModal ? createModal.querySelectorAll('[data-treatments-create-close]') : [];

    var viewModal = document.getElementById('treatments-view-modal');
    var viewName = viewModal ? viewModal.querySelector('[data-treatments-view-name]') : null;
    var viewDescription = viewModal ? viewModal.querySelector('[data-treatments-view-description]') : null;
    var viewTime = viewModal ? viewModal.querySelector('[data-treatments-view-time]') : null;
    var viewCloseButtons = viewModal ? viewModal.querySelectorAll('[data-treatments-view-close]') : [];

    var editModal = document.getElementById('treatments-edit-modal');
    var editForm = editModal ? editModal.querySelector('[data-treatments-edit-form]') : null;
    var editId = editModal ? editModal.querySelector('[data-treatments-edit-id]') : null;
    var editName = editModal ? editModal.querySelector('[data-treatments-edit-name]') : null;
    var editDescription = editModal ? editModal.querySelector('[data-treatments-edit-description]') : null;
    var editTime = editModal ? editModal.querySelector('[data-treatments-edit-time]') : null;
    var editSubmit = editModal ? editModal.querySelector('[data-treatments-edit-submit]') : null;
    var editCloseButtons = editModal ? editModal.querySelectorAll('[data-treatments-edit-close]') : [];

    var deleteModal = document.getElementById('treatments-delete-modal');
    var deleteForm = deleteModal ? deleteModal.querySelector('[data-treatments-delete-form]') : null;
    var deleteId = deleteModal ? deleteModal.querySelector('[data-treatments-delete-id]') : null;
    var deleteTarget = deleteModal ? deleteModal.querySelector('[data-treatments-delete-target]') : null;
    var deleteSubmit = deleteModal ? deleteModal.querySelector('[data-treatments-delete-submit]') : null;
    var deleteCloseButtons = deleteModal ? deleteModal.querySelectorAll('[data-treatments-delete-close]') : [];

    var allTreatments = [];
    var isCreateSubmitting = false;
    var isEditSubmitting = false;
    var isDeleteSubmitting = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    function showError(message) {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function hideError() {
        if (!errorBox) {
            return;
        }

        errorBox.textContent = '';
        errorBox.classList.add('hidden');
    }

    function setLoading(isLoading) {
        if (loadingBox) {
            loadingBox.classList.toggle('hidden', !isLoading);
        }
    }

    function normalizeTreatmentsPayload(payload) {
        if (Array.isArray(payload?.data)) {
            return payload.data;
        }

        if (payload && typeof payload === 'object' && ('name' in payload || 'description' in payload || 'time' in payload)) {
            return [payload];
        }

        return Array.isArray(payload) ? payload : [];
    }

    function normalizeTreatment(record) {
        return {
            id: record?.id ?? '',
            name: String(record?.name ?? '').trim(),
            description: String(record?.description ?? '').trim(),
            time: record?.time === null || record?.time === undefined || record?.time === '' ? '' : String(record.time),
        };
    }

    function formatTime(value) {
        var numericValue = Number(value);

        if (!Number.isFinite(numericValue) || numericValue < 0) {
            return 'Sin duración';
        }

        return numericValue + ' min';
    }

    function setModalVisibility(modal, isVisible) {
        if (!modal) {
            return;
        }

        if (isVisible) {
            openDialog(modal);
        } else {
            closeDialog(modal);
        }
    }

    function messageOf(error) {
        return error instanceof TypeError ? failureMessage(0) : error.message;
    }

    function setButtonState(button, submitting) {
        if (!button) {
            return;
        }

        button.disabled = submitting;
    }

    function closeAllModals() {
        setModalVisibility(createModal, false);
        setModalVisibility(viewModal, false);
        setModalVisibility(editModal, false);
        setModalVisibility(deleteModal, false);
    }

    function openCreateModal() {
        if (!createModal) {
            return;
        }

        clearAnnouncements();

        if (createForm) {
            createForm.reset();
        }

        setModalVisibility(createModal, true);

        if (createName) {
            createName.focus();
        }
    }

    function openViewModal(treatment) {
        if (!viewModal) {
            return;
        }

        if (viewName) {
            viewName.textContent = treatment.name || '-';
        }

        if (viewDescription) {
            viewDescription.textContent = treatment.description || '-';
        }

        if (viewTime) {
            viewTime.textContent = formatTime(treatment.time);
        }

        setModalVisibility(viewModal, true);
    }

    function openEditModal(treatment) {
        if (!editModal) {
            return;
        }

        clearAnnouncements();

        if (editId) {
            editId.value = String(treatment.id ?? '');
        }

        if (editName) {
            editName.value = treatment.name || '';
        }

        if (editDescription) {
            editDescription.value = treatment.description || '';
        }

        if (editTime) {
            editTime.value = treatment.time === '' ? '' : String(treatment.time);
        }

        setModalVisibility(editModal, true);

        if (editName) {
            editName.focus();
        }
    }

    function openDeleteModal(treatment) {
        if (!deleteModal) {
            return;
        }

        clearAnnouncements();

        if (deleteId) {
            deleteId.value = String(treatment.id ?? '');
        }

        if (deleteTarget) {
            deleteTarget.textContent = treatment.name || 'este tratamiento';
        }

        setModalVisibility(deleteModal, true);
    }

    function applyFilters() {
        var query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        if (!query) {
            renderTreatments(allTreatments);
            return;
        }

        var filtered = allTreatments.filter(function (record) {
            var name = String(record.name ?? '').toLowerCase();
            var description = String(record.description ?? '').toLowerCase();
            var time = String(record.time ?? '').toLowerCase();

            return name.includes(query) || description.includes(query) || time.includes(query);
        });

        renderTreatments(filtered);
    }

    function renderTreatments(records) {
        if (!listEl) {
            return;
        }

        var total = allTreatments.length;
        var withTime = 0;
        var totalDuration = 0;

        allTreatments.forEach(function (record) {
            var numericTime = Number(record.time);

            if (Number.isFinite(numericTime) && numericTime >= 0) {
                withTime += 1;
                totalDuration += numericTime;
            }
        });

        if (totalCountEl) {
            totalCountEl.textContent = String(total);
        }

        if (withTimeCountEl) {
            withTimeCountEl.textContent = String(withTime);
        }

        if (averageTimeEl) {
            averageTimeEl.textContent = withTime > 0 ? Math.round(totalDuration / withTime) + ' min' : '0 min';
        }

        if (records.length === 0) {
            listEl.innerHTML = '';

            if (emptyBox) {
                emptyBox.classList.remove('hidden');
            }

            return;
        }

        if (emptyBox) {
            emptyBox.classList.add('hidden');
        }

        listEl.innerHTML = records.map(function (record) {
            var treatment = normalizeTreatment(record);
            var id = treatment.id;
            var name = treatment.name || 'Sin nombre';
            var description = treatment.description || 'Sin descripción';
            var durationLabel = formatTime(treatment.time);

            return [
                '<article class="flex flex-col rounded-card border border-line bg-surface p-4 shadow-sm md:p-5">',
                    '<div class="flex items-start justify-between gap-3">',
                        '<h2 class="min-w-0 wrap-break-word text-base font-semibold text-ink">', escapeHtml(name), '</h2>',
                        '<span class="shrink-0 rounded-full bg-canvas px-2.5 py-0.5 text-min font-bold text-muted">', escapeHtml(durationLabel), '</span>',
                    '</div>',

                    '<p class="mt-2 line-clamp-3 wrap-break-word text-sm leading-6 text-muted">', escapeHtml(description), '</p>',

                    '<div class="mt-4 flex items-center gap-2">',
                        '<button type="button" data-treatments-view data-treatment-id="', escapeHtml(id), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Ver detalle</button>',
                        '<button type="button" data-treatments-edit data-treatment-id="', escapeHtml(id), '" class="inline-flex min-h-control flex-1 items-center justify-center rounded-control border border-field bg-surface px-4 text-sm font-semibold text-ink transition-colors hover:bg-canvas">Editar</button>',
                        '<button type="button" data-treatments-delete data-treatment-id="', escapeHtml(id), '" data-treatment-name="', escapeHtml(name), '" aria-label="Eliminar ', escapeHtml(name), '" class="inline-flex size-control shrink-0 items-center justify-center rounded-control border border-field bg-surface text-ink transition-colors hover:bg-danger-soft hover:text-danger">',
                            '<svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>',
                        '</button>',
                    '</div>',
                '</article>',
            ].join('');
        }).join('');
    }

    async function requestJson(url, options) {
        var xsrfToken = getCookie('XSRF-TOKEN');
        var requestOptions = options || {};
        var requestHeaders = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken || '',
        }, requestOptions.headers || {});

        var response = await fetch(url, Object.assign({
            credentials: 'include',
        }, requestOptions, {
            headers: requestHeaders,
        }));

        var payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(failureMessage(response.status, payload));
        }

        return payload;
    }

    async function loadTreatments() {
        hideError();
        setLoading(true);

        try {
            var payload = await requestJson('/api/v1/treatments', { method: 'GET' });
            allTreatments = normalizeTreatmentsPayload(payload).map(normalizeTreatment);
            applyFilters();
        } catch (error) {
            showError(messageOf(error));
            allTreatments = [];
            renderTreatments([]);
        } finally {
            setLoading(false);
        }
    }

    async function fetchTreatmentById(id) {
        var payload = await requestJson('/api/v1/treatments/' + encodeURIComponent(String(id)), { method: 'GET' });
        return normalizeTreatment(payload?.data ?? payload);
    }

    async function createTreatment(payload) {
        await requestJson('/api/v1/treatments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
    }

    async function updateTreatment(id, payload) {
        await requestJson('/api/v1/treatments/' + encodeURIComponent(String(id)), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
    }

    async function deleteTreatment(id) {
        await requestJson('/api/v1/treatments/' + encodeURIComponent(String(id)), {
            method: 'DELETE',
        });
    }

    if (createButton) {
        createButton.addEventListener('click', openCreateModal);
    }

    createCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (isCreateSubmitting) {
                return;
            }

            setModalVisibility(createModal, false);
            clearAnnouncements();
        });
    });

    viewCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalVisibility(viewModal, false);
        });
    });

    editCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (isEditSubmitting) {
                return;
            }

            setModalVisibility(editModal, false);
            clearAnnouncements();
        });
    });

    deleteCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (isDeleteSubmitting) {
                return;
            }

            setModalVisibility(deleteModal, false);
            clearAnnouncements();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (listEl) {
        listEl.addEventListener('click', async function (event) {
            var viewButton = event.target.closest('[data-treatments-view]');
            var editButton = event.target.closest('[data-treatments-edit]');
            var deleteButton = event.target.closest('[data-treatments-delete]');

            if (viewButton) {
                var viewIdValue = viewButton.getAttribute('data-treatment-id');

                if (!viewIdValue) {
                    return;
                }

                try {
                    hideError();
                    var viewTreatment = await fetchTreatmentById(viewIdValue);
                    openViewModal(viewTreatment);
                } catch (error) {
                    showError(messageOf(error));
                }

                return;
            }

            if (editButton) {
                var editIdValue = editButton.getAttribute('data-treatment-id');

                if (!editIdValue) {
                    return;
                }

                try {
                    hideError();
                    var editTreatment = await fetchTreatmentById(editIdValue);
                    openEditModal(editTreatment);
                } catch (error) {
                    showError(messageOf(error));
                }

                return;
            }

            if (deleteButton) {
                var deleteIdValue = deleteButton.getAttribute('data-treatment-id');
                var treatmentName = deleteButton.getAttribute('data-treatment-name') || 'este tratamiento';

                if (!deleteIdValue) {
                    return;
                }

                openDeleteModal({
                    id: deleteIdValue,
                    name: treatmentName,
                });
            }
        });
    }

    // El navegador frena el envío de un campo obligatorio vacío sin decir nada a un lector de
    // pantalla: cada campo que rechaza queda marcado con su error (spec 016, CA37).
    [createForm, editForm].forEach(function (form) {
        if (!form) {
            return;
        }

        form.addEventListener('invalid', function (event) {
            var field = event.target;
            markFieldErrors(form, { [field.name]: [field.validity.valueMissing ? 'Este campo es obligatorio.' : field.validationMessage] });
        }, true);
    });

    if (createForm) {
        createForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isCreateSubmitting) {
                return;
            }

            var payload = {
                name: createName ? createName.value.trim() : '',
                description: createDescription ? createDescription.value.trim() : '',
                time: createTime ? createTime.value.trim() : '',
            };

            clearAnnouncements();
            isCreateSubmitting = true;
            setButtonState(createSubmit, true);

            try {
                await createTreatment(payload);
                closeAllModals();
                announce('Tratamiento creado.');
                await loadTreatments();
            } catch (error) {
                announceError(messageOf(error));
            } finally {
                isCreateSubmitting = false;
                setButtonState(createSubmit, false);
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isEditSubmitting) {
                return;
            }

            var treatmentId = editId ? editId.value.trim() : '';

            if (!treatmentId) {
                announceError('No pudimos identificar el tratamiento. Cierra el diálogo e inténtalo de nuevo.');
                return;
            }

            var payload = {
                name: editName ? editName.value.trim() : '',
                description: editDescription ? editDescription.value.trim() : '',
                time: editTime ? editTime.value.trim() : '',
            };

            clearAnnouncements();
            isEditSubmitting = true;
            setButtonState(editSubmit, true);

            try {
                await updateTreatment(treatmentId, payload);
                closeAllModals();
                announce('Cambios guardados.');
                await loadTreatments();
            } catch (error) {
                announceError(messageOf(error));
            } finally {
                isEditSubmitting = false;
                setButtonState(editSubmit, false);
            }
        });
    }

    if (deleteForm) {
        deleteForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isDeleteSubmitting) {
                return;
            }

            var treatmentId = deleteId ? deleteId.value.trim() : '';

            if (!treatmentId) {
                announceError('No pudimos identificar el tratamiento. Cierra el diálogo e inténtalo de nuevo.');
                return;
            }

            clearAnnouncements();
            isDeleteSubmitting = true;
            setButtonState(deleteSubmit, true);

            try {
                await deleteTreatment(treatmentId);
                closeAllModals();
                announce('Tratamiento eliminado.');
                await loadTreatments();
            } catch (error) {
                announceError(messageOf(error));
            } finally {
                isDeleteSubmitting = false;
                setButtonState(deleteSubmit, false);
            }
        });
    }

    window.treatmentsPage = {
        reload: loadTreatments,
        showError: showError,
        hideError: hideError,
    };

    loadTreatments();
})();