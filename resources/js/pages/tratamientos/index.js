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
    var createError = createModal ? createModal.querySelector('[data-treatments-create-error]') : null;
    var createSubmit = createModal ? createModal.querySelector('[data-treatments-create-submit]') : null;
    var createCloseButtons = createModal ? createModal.querySelectorAll('[data-treatments-create-close]') : [];

    var viewModal = document.getElementById('treatments-view-modal');
    var viewName = viewModal ? viewModal.querySelector('[data-treatments-view-name]') : null;
    var viewDescription = viewModal ? viewModal.querySelector('[data-treatments-view-description]') : null;
    var viewTime = viewModal ? viewModal.querySelector('[data-treatments-view-time]') : null;
    var viewId = viewModal ? viewModal.querySelector('[data-treatments-view-id]') : null;
    var viewCloseButtons = viewModal ? viewModal.querySelectorAll('[data-treatments-view-close]') : [];

    var editModal = document.getElementById('treatments-edit-modal');
    var editForm = editModal ? editModal.querySelector('[data-treatments-edit-form]') : null;
    var editId = editModal ? editModal.querySelector('[data-treatments-edit-id]') : null;
    var editName = editModal ? editModal.querySelector('[data-treatments-edit-name]') : null;
    var editDescription = editModal ? editModal.querySelector('[data-treatments-edit-description]') : null;
    var editTime = editModal ? editModal.querySelector('[data-treatments-edit-time]') : null;
    var editError = editModal ? editModal.querySelector('[data-treatments-edit-error]') : null;
    var editSubmit = editModal ? editModal.querySelector('[data-treatments-edit-submit]') : null;
    var editCloseButtons = editModal ? editModal.querySelectorAll('[data-treatments-edit-close]') : [];

    var deleteModal = document.getElementById('treatments-delete-modal');
    var deleteForm = deleteModal ? deleteModal.querySelector('[data-treatments-delete-form]') : null;
    var deleteId = deleteModal ? deleteModal.querySelector('[data-treatments-delete-id]') : null;
    var deleteTarget = deleteModal ? deleteModal.querySelector('[data-treatments-delete-target]') : null;
    var deleteError = deleteModal ? deleteModal.querySelector('[data-treatments-delete-error]') : null;
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
            return 'Sin duracion';
        }

        return numericValue + ' min';
    }

    function setModalVisibility(modal, isVisible) {
        if (!modal) {
            return;
        }

        modal.classList.toggle('hidden', !isVisible);
        document.body.style.overflow = isVisible ? 'hidden' : '';
    }

    function setButtonState(button, submitting) {
        if (!button) {
            return;
        }

        button.disabled = submitting;
    }

    function showModalError(node, message) {
        if (!node) {
            return;
        }

        node.textContent = message;
        node.classList.remove('hidden');
    }

    function hideModalError(node) {
        if (!node) {
            return;
        }

        node.textContent = '';
        node.classList.add('hidden');
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

        hideModalError(createError);

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

        if (viewId) {
            viewId.textContent = treatment.id ? String(treatment.id) : '-';
        }

        setModalVisibility(viewModal, true);
    }

    function openEditModal(treatment) {
        if (!editModal) {
            return;
        }

        hideModalError(editError);

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

        hideModalError(deleteError);

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
            var description = treatment.description || 'Sin descripcion';
            var durationLabel = formatTime(treatment.time);
            var initial = (name.charAt(0) || 'T').toUpperCase();
            var colorClasses = ['bg-[#FDF1F6] text-[#B5114A]', 'bg-sky-50 text-sky-700', 'bg-emerald-50 text-emerald-700', 'bg-amber-50 text-amber-700'];
            var colorIndex = (name.length + description.length) % colorClasses.length;
            var avatarClasses = colorClasses[colorIndex];

            return [
                '<article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-[#F5C2D6] hover:shadow-md">',
                    '<div class="flex items-start justify-between gap-4">',
                        '<div class="flex items-center gap-3">',
                            '<div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ' + avatarClasses + ' text-sm font-bold transition-transform group-hover:scale-110">', escapeHtml(initial), '</div>',
                            '<div class="min-w-0">',
                                '<p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Tratamiento</p>',
                                '<h3 class="mt-1 wrap-break-word text-base font-semibold text-slate-900 transition-colors group-hover:text-[#B5114A]">', escapeHtml(name), '</h3>',
                            '</div>',
                        '</div>',
                        '<span class="inline-flex shrink-0 rounded-full bg-[#FDF1F6] px-3 py-1 text-xs font-semibold text-[#B5114A]">', escapeHtml(durationLabel), '</span>',
                    '</div>',

                    '<p class="mt-4 line-clamp-3 wrap-break-word text-sm leading-6 text-slate-600">', escapeHtml(description), '</p>',

                    '<div class="mt-4 flex flex-wrap items-center justify-between gap-3">',
                        '<button type="button" data-treatments-view data-treatment-id="', escapeHtml(id), '" class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">',
                            'Ver detalle',
                        '</button>',
                        '<div class="flex flex-wrap gap-2">',
                            '<button type="button" data-treatments-edit data-treatment-id="', escapeHtml(id), '" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50">Editar</button>',
                            '<button type="button" data-treatments-delete data-treatment-id="', escapeHtml(id), '" data-treatment-name="', escapeHtml(name), '" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Eliminar</button>',
                        '</div>',
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
            throw new Error(payload.error || payload.message || 'Operacion fallida.');
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
            showError(error.message || 'No se pudieron cargar los tratamientos.');
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
            hideModalError(createError);
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
            hideModalError(editError);
        });
    });

    deleteCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (isDeleteSubmitting) {
                return;
            }

            setModalVisibility(deleteModal, false);
            hideModalError(deleteError);
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
                    showError(error.message || 'No se pudo cargar el tratamiento.');
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
                    showError(error.message || 'No se pudo cargar el tratamiento.');
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

            hideModalError(createError);
            isCreateSubmitting = true;
            setButtonState(createSubmit, true);

            try {
                await createTreatment(payload);
                closeAllModals();
                await loadTreatments();
            } catch (error) {
                showModalError(createError, error.message || 'No se pudo crear el tratamiento.');
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
                showModalError(editError, 'No se pudo identificar el tratamiento.');
                return;
            }

            var payload = {
                name: editName ? editName.value.trim() : '',
                description: editDescription ? editDescription.value.trim() : '',
                time: editTime ? editTime.value.trim() : '',
            };

            hideModalError(editError);
            isEditSubmitting = true;
            setButtonState(editSubmit, true);

            try {
                await updateTreatment(treatmentId, payload);
                closeAllModals();
                await loadTreatments();
            } catch (error) {
                showModalError(editError, error.message || 'No se pudo actualizar el tratamiento.');
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
                showModalError(deleteError, 'No se pudo identificar el tratamiento.');
                return;
            }

            hideModalError(deleteError);
            isDeleteSubmitting = true;
            setButtonState(deleteSubmit, true);

            try {
                await deleteTreatment(treatmentId);
                closeAllModals();
                await loadTreatments();
            } catch (error) {
                showModalError(deleteError, error.message || 'No se pudo eliminar el tratamiento.');
            } finally {
                isDeleteSubmitting = false;
                setButtonState(deleteSubmit, false);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        closeAllModals();
    });

    window.treatmentsPage = {
        reload: loadTreatments,
        showError: showError,
        hideError: hideError,
    };

    loadTreatments();
})();