import { announce, announceError, announceFailure, clearAnnouncements } from '../../ui/status';

(function () {
    const form = document.getElementById('forgot-form');
    const errorBox = document.getElementById('forgot-error');
    const successBox = document.getElementById('forgot-success');
    const submitButton = document.getElementById('forgot-submit');

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

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const emailInput = form.querySelector('input[name="email"]');
        const email = emailInput ? emailInput.value.trim() : '';

        if (errorBox) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }
        clearAnnouncements();
        if (successBox) {
            successBox.classList.add('hidden');
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            // Initialize Sanctum CSRF cookie
            const csrfResponse = await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include',
            });

            if (!csrfResponse.ok) {
                throw new Error('csrf');
            }

            const xsrfToken = getCookie('XSRF-TOKEN');

            const response = await fetch('/api/v1/auth/send-reset-password-email', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken || '',
                },
                credentials: 'include',
                body: JSON.stringify({ email: email }),
            });

            const payload = await response.json().catch(function () {
                return {};
            });

            if (!response.ok) {
                announceFailure(response.status, payload);
                return;
            }

            if (successBox) {
                successBox.classList.remove('hidden');
                announce(successBox.textContent.trim());
            }
            
            if (form) {
                form.reset();
            }
        } catch (error) {
            announceFailure(0);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
})();
