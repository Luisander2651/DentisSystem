<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa - Clínica Dental')</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-180.png') }}">

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen w-full flex-col bg-canvas text-ink antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-100 focus:inline-flex focus:min-h-control focus:items-center focus:rounded-control focus:bg-surface focus:px-4 focus:text-sm focus:font-semibold focus:text-ink">Saltar al contenido</a>

    <!-- Navbar Component -->
    <x-landing.nav />

    <!-- Main Content Area -->
    <main id="contenido" tabindex="-1" class="flex-1">
        @yield('content')
    </main>

    <!-- Footer Component -->
    <x-landing.footer />

    <x-ui.status />
</body>
</html>
