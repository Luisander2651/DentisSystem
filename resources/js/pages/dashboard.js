import { announce } from '../ui/status';

(function () {
    if (window.__dashboardInit) {
        return;
    }

    window.__dashboardInit = true;

    // The dialogs to create a patient and a user also open from the start of the panel. Here
    // there is no listing to reload: saving is announced in the status region.
    window.patientsPage = {
        hideError: function () {},
        showError: function () {},
        showSuccess: function (message) {
            announce(message);
        },
        reload: function () {
            announce('Paciente creado.');
        },
    };

    window.usersPage = {
        hideError: function () {},
        showError: function () {},
        showSuccess: function (message) {
            announce(message);
        },
        reload: function () {
            announce('Usuario creado.');
        },
    };
})();
