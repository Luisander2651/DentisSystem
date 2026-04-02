<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar sesion</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800">
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-6">
        <div class="w-full rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Cerrando sesion...</h1>
            <p id="logout-status" class="mt-2 text-sm text-slate-500">Estamos finalizando tu sesion de forma segura.</p>
        </div>
    </main>

    <script>
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
                        status.textContent = 'No se pudo confirmar el logout en API. Redirigiendo a login...';
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
