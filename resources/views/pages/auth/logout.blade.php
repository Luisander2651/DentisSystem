<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar sesión</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-180.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink">
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-6">
        <div class="w-full rounded-card border border-line bg-surface p-8 text-center shadow-sm">
            <x-ui.brand variant="logo" class="mx-auto mb-6 w-48" />
            <h1 class="text-xl font-semibold text-ink">Cerrando sesión...</h1>
            <p id="logout-status" class="mt-2 text-sm text-muted">Estamos cerrando tu sesión de forma segura.</p>
        </div>
    </main>

    <x-ui.status />

    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            var status = document.getElementById('logout-status');

            function getCookie(name) {
                var value = '; ' + document.cookie;
                var parts = value.split('; ' + name + '=');

                if (parts.length === 2) {
                    return decodeURIComponent(parts.pop().split(';').shift());
                }

                return null;
            }

            async function closeSession() {
                try {
                    await fetch('/sanctum/csrf-cookie', {
                        method: 'GET',
                        credentials: 'include',
                    });

                    var xsrfToken = getCookie('XSRF-TOKEN');

                    await fetch('/api/v1/auth/logout', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-XSRF-TOKEN': xsrfToken || '',
                        },
                        credentials: 'include',
                    });
                } catch (error) {
                    if (status) {
                        status.textContent = 'No pudimos confirmar el cierre de sesión. Te llevamos a la pantalla de acceso...';
                    }
                } finally {
                    window.location.href = '{{ url('/login') }}';
                }
            }

            closeSession();
        })();
    </script>
</body>
</html>
