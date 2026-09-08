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
            errorBox.textContent = 'Token de restablecimiento no válido o ausente. Solicita un nuevo enlace.';
            errorBox.classList.remove('hidden');
        }
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => input.disabled = true);
        if (submitButton) {
            submitButton.disabled = true;
        }
        return;
    }

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const passwordInput = form.querySelector('input[name="password"]');
        const confirmPasswordInput = form.querySelector('input[name="confirm_password"]');

        // BR-23: trim the borders here too, so what the user sees compared on screen
        // is exactly what the server will store. Internal spaces are preserved.
        const password = passwordInput ? passwordInput.value.trim() : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value.trim() : '';

        if (errorBox) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }

        // Validar coincidencia en cliente antes de enviar
        if (password !== confirmPassword) {
            if (errorBox) {
                errorBox.textContent = 'Las contraseñas ingresadas no coinciden.';
                errorBox.classList.remove('hidden');
            }
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
                throw new Error('No se pudo inicializar la cookie CSRF.');
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
                const message = payload.error || payload.message || 'No se pudo restablecer la contraseña.';
                if (errorBox) {
                    errorBox.textContent = message;
                    errorBox.classList.remove('hidden');
                }
                return;
            }

            if (form) {
                form.classList.add('hidden');
            }
            if (successBox) {
                successBox.classList.remove('hidden');
            }

        } catch (error) {
            if (errorBox) {
                errorBox.textContent = 'Error de conexión. Inténtalo de nuevo.';
                errorBox.classList.remove('hidden');
            }
        } finally {
            if (submitButton && !successBox.classList.contains('hidden')) {
                submitButton.disabled = false;
            }
        }
    });
})();
