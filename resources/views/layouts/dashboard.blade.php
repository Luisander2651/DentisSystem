<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="w-full min-h-screen bg-slate-50">
    <div class="flex gap-6 p-4 md:p-6">
        <!-- Sidebar -->
        <div class="hidden md:block md:sticky md:top-6 md:h-fit">
            <x-ui.sidebar role="{{ $sidebarRole ?? 'usuario' }}" />
        </div>

        <!-- Main Content -->
        <main class="w-full flex-1">
            <!-- Mobile Sidebar Toggle (optional) -->
            <div class="mb-6 md:hidden">
                <x-ui.sidebar role="{{ $sidebarRole ?? 'usuario' }}" />
            </div>

            <!-- Page Content -->
            <div class="space-y-6">
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Footer (opcional) -->
    <footer class="mt-12 border-t border-slate-200 bg-white py-6 px-4 text-center text-sm text-slate-600">
        <p>&copy; {{ date('Y') }} Dentissa. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
