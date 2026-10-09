<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-180.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="w-full min-h-screen bg-canvas text-ink">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-100 focus:inline-flex focus:min-h-control focus:items-center focus:rounded-control focus:bg-surface focus:px-4 focus:text-sm focus:font-semibold focus:text-ink">Saltar al contenido</a>

    <div class="flex gap-6 p-4 md:p-6">
        <!-- Sidebar -->
        <div class="hidden md:block md:sticky md:top-6 md:h-fit">
            <x-ui.sidebar role="{{ $sidebarRole ?? 'usuario' }}" />
        </div>

        <!-- Main Content -->
        <main id="contenido" tabindex="-1" class="w-full flex-1">
            <!-- Mobile Sidebar Toggle (optional) -->
            <div class="mb-6 md:hidden">
                <x-ui.sidebar role="{{ $sidebarRole ?? 'usuario' }}" />
            </div>

            <!-- Page Content -->
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-box border border-success bg-success-soft px-4 py-3 text-sm font-semibold text-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-box border border-danger bg-danger-soft px-4 py-3 text-sm font-semibold text-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Footer (opcional) -->
    <footer class="mt-12 border-t border-line bg-surface py-6 px-4 text-center text-sm text-muted">
        <p>&copy; {{ date('Y') }} Dentissa. Todos los derechos reservados.</p>
    </footer>

    <x-ui.status />
</body>
</html>
