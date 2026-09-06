<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa - Clínica Dental')</title>

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="w-full min-h-screen bg-slate-50/50 text-slate-800 flex flex-col antialiased">
    <!-- Navbar Component -->
    <x-landing.nav />

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer Component -->
    <x-landing.footer />
</body>
</html>
