(function () {
    if (window.__dashboardInit) {
        return;
    }

    window.__dashboardInit = true;

    // Setup for Patients modal in dashboard
    var dashboardPatientsSuccess = document.getElementById('dashboard-patients-success');

    window.patientsPage = {
        hideError: function () {
            // No-op on dashboard
        },
        showError: function (message) {
            // Errors are shown in modal only
        },
        showSuccess: function (message) {
            if (dashboardPatientsSuccess) {
                dashboardPatientsSuccess.textContent = message;
                dashboardPatientsSuccess.classList.remove('hidden');
            }
        },
        reload: function () {
            // On dashboard, just show success message
            this.showSuccess('¡Paciente creado exitosamente!');
            setTimeout(function () {
                if (dashboardPatientsSuccess) {
                    dashboardPatientsSuccess.textContent = '';
                    dashboardPatientsSuccess.classList.add('hidden');
                }
            }, 3000);
        }
    };

    // Setup for Users modal in dashboard
    var dashboardUsersSuccess = document.getElementById('dashboard-users-success');

    window.usersPage = {
        hideError: function () {
            // No-op on dashboard
        },
        showError: function (message) {
            // Errors are shown in modal only
        },
        showSuccess: function (message) {
            if (dashboardUsersSuccess) {
                dashboardUsersSuccess.textContent = message;
                dashboardUsersSuccess.classList.remove('hidden');
            }
        },
        reload: function () {
            // On dashboard, just show success message
            this.showSuccess('¡Usuario creado exitosamente!');
            setTimeout(function () {
                if (dashboardUsersSuccess) {
                    dashboardUsersSuccess.textContent = '';
                    dashboardUsersSuccess.classList.add('hidden');
                }
            }, 3000);
        }
    };
})();
