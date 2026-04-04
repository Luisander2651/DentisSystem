(function () {
    if (window.__patientsDeleteInit) {
        return;
    }

    window.__patientsDeleteInit = true;

    function getCookie(name) {
        var value = '; ' + document.cookie;
        var parts = value.split('; ' + name + '=');

        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }

        return null;
    }

    var table = document.getElementById('patients-table');
    var modal = document.getElementById('patients-delete-modal');

    if (!table || !modal) {
        return;
    }

    var confirmTarget = modal.querySelector('[data-confirm-target]');
    var cancelButton = modal.querySelector('[data-confirm-cancel]');
    var acceptButton = modal.querySelector('[data-confirm-accept]');

    var currentPatientId = null;
    var isDeleting = false;

    function openModal(patientId, patientLabel) {
        currentPatientId = patientId;

        if (confirmTarget) {
            confirmTarget.textContent = patientLabel || 'este registro';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        currentPatientId = null;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function deletePatientById(patientId) {
        var xsrfToken = getCookie('XSRF-TOKEN');

        var response = await fetch('/api/v1/patients/' + encodeURIComponent(patientId), {
            method: 'DELETE',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken || '',
            },
        });

        var payload = await response.json().catch(function () {
            return {};
        });

        if (!response.ok) {
            throw new Error(payload.error || payload.message || 'No se pudo eliminar el paciente.');
        }
    }

    table.addEventListener('click', function (event) {
        var deleteButton = event.target.closest('[data-patient-delete]');

        if (!deleteButton || deleteButton.disabled) {
            return;
        }

        var patientId = deleteButton.getAttribute('data-patient-id');
        var patientLabel = deleteButton.getAttribute('data-patient-label');

        if (!patientId) {
            return;
        }

        openModal(patientId, patientLabel);
    });

    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (acceptButton) {
        acceptButton.addEventListener('click', async function () {
            if (!currentPatientId || isDeleting) {
                return;
            }

            isDeleting = true;
            acceptButton.disabled = true;

            try {
                await deletePatientById(currentPatientId);
                closeModal();

                if (window.patientsPage && typeof window.patientsPage.hideError === 'function') {
                    window.patientsPage.hideError();
                }

                if (window.patientsPage && typeof window.patientsPage.reload === 'function') {
                    await window.patientsPage.reload();
                }
            } catch (error) {
                closeModal();

                if (window.patientsPage && typeof window.patientsPage.showError === 'function') {
                    window.patientsPage.showError(error.message || 'No se pudo eliminar el paciente.');
                }
            } finally {
                isDeleting = false;
                acceptButton.disabled = false;
            }
        });
    }
})();