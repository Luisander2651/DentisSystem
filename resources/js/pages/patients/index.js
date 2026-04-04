(function () {
    if (window.__patientsIndexInit) {
        return;
    }

    window.__patientsIndexInit = true;

    var tableId = 'patients-table';
    var errorBox = document.getElementById('patients-error');

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

    function normalizeStatus(status) {
        switch (status) {
            case 'active':
                return 'Activo';
            case 'inactive':
                return 'Inactivo';
            default:
                return '-';
        }
    }

    function normalizePatients(payload) {
        var records = Array.isArray(payload && payload.data)
            ? payload.data
            : (Array.isArray(payload) ? payload : []);

        return records.map(function (patient) {
            var firstName = patient && patient.first_name ? String(patient.first_name).trim() : '';
            var lastName = patient && patient.last_name ? String(patient.last_name).trim() : '';
            var email = patient && patient.email ? String(patient.email).trim() : '';

            return {
                id: patient && patient.id ? patient.id : null,
                full_name: (firstName + ' ' + lastName).trim(),
                first_name: firstName !== '' ? firstName : '-',
                first_name_value: firstName,
                last_name: lastName !== '' ? lastName : '-',
                last_name_value: lastName,
                email: email !== '' ? email : '-',
                status: normalizeStatus(patient && patient.status ? patient.status : ''),
                status_value: patient && patient.status ? patient.status : '',
            };
        });
    }

    async function loadPatients() {
        hideError();

        try {
            var response = await fetch('/api/v1/patients', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            var payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                var message = payload.error || payload.message || 'No se pudieron cargar los pacientes.';
                showError(message);
                window.renderUiTable(tableId, []);
                return;
            }

            window.renderUiTable(tableId, normalizePatients(payload));
        } catch (error) {
            showError('Error de conexion. Intentalo de nuevo.');
            window.renderUiTable(tableId, []);
        }
    }

    window.patientsPage = {
        reload: loadPatients,
        showError: showError,
        hideError: hideError,
    };

    document.addEventListener('DOMContentLoaded', loadPatients);
})();
