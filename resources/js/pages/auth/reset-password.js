import { announce, announceError, announceFailure, clearAnnouncements } from '../../ui/status';

(function () {
    const form = document.getElementById('reset-form');
    const errorBox = document.getElementById('reset-error');
    const successBox = document.getElementById('reset-success');
    const submitButton = document.getElementById('reset-submit');

    // Leer el token desde los parámetros de la URL
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return decodeURIComponent(parts.pop().split(';').shift());
        }
        return null;
    }

    if (!form) {
        return;
    }

    // Si no hay token en la URL, mostrar error inmediato y deshabilitar
    if (!token) {
        if (errorBox) {
            errorBox.textContent = 'El enlace de restablecimiento no es válido o ya caducó. Solicita uno nuevo.';
            errorBox.classList.remove('hidden');
        }
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => input.disabled = true);
        if (submitButton) {
            submitButton.disabled = true;
        }
        return;
    }

    const passwordInput = form.querySelector('input[name="password"]');
    const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');
    const mismatchMessage = 'Las contraseñas no coinciden.';
    let mismatchShown = false;

    // El error de contraseñas distintas queda en el campo de confirmación, igual que los del
    // componente de campo (spec 016, CA37), y se mantiene mientras sigan sin coincidir.
    function showMismatch() {
        const errorTarget = document.getElementById(confirmPasswordInput.dataset.errorTarget);
        const describedBy = (confirmPasswordInput.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id !== '' && id !== errorTarget.id);

        errorTarget.textContent = mismatchMessage;
        errorTarget.classList.remove('hidden');
        confirmPasswordInput.classList.remove('border-field');
        confirmPasswordInput.classList.add('border-2', 'border-danger');
        confirmPasswordInput.setAttribute('aria-invalid', 'true');
        confirmPasswordInput.setAttribute('aria-describedby', describedBy.concat(errorTarget.id).join(' '));
        mismatchShown = true;
    }

    function passwordsMatch() {
        return passwordInput.value.trim() === confirmPasswordInput.value.trim();
    }

    // El campo vuelve a validarse solo al escribir o salir de él; si aún no coinciden, el error sigue.
    ['input', 'blur'].forEach((type) => {
        confirmPasswordInput.addEventListener(type, () => {
            if (mismatchShown && !passwordsMatch()) {
                showMismatch();
            } else {
                mismatchShown = false;
            }
        });
    });

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        // BR-23: trim the borders here too, so what the user sees compared on screen
        // is exactly what the server will store. Internal spaces are preserved.
        const password = passwordInput ? passwordInput.value.trim() : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

        if (errorBox) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }
        clearAnnouncements();

        // Validar coincidencia en cliente antes de enviar
        if (password !== confirmPassword) {
            showMismatch();
            announceError(mismatchMessage);
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            // Obtener cookie CSRF
            const csrfResponse = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include',
            });

            if (!csrfResponse.ok) {
                throw new Error('csrf');
            }

            const xsrfToken = getCookie('XSRF-TOKEN');

            // Enviar el token y el password en el body: el token no debe viajar en la
            // query string, donde quedaría registrado en logs de acceso e historial.
            const response = await fetch('/api/v1/auth/reset-password', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken || '',
                },
                credentials: 'include',
                body: JSON.stringify({
                    token: token,
                    new_password: password
                }),
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                announceFailure(response.status, payload);
                return;
            }

            if (form) {
                form.classList.add('hidden');
            }
            if (successBox) {
                successBox.classList.remove('hidden');
                announce(successBox.textContent.trim());
            }

        } catch (error) {
            announceFailure(0);
        } finally {
            if (submitButton && !successBox.classList.contains('hidden')) {
                submitButton.disabled = false;
            }
        }
    });
})();
