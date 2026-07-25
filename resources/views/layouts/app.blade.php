<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(181,17,74,0.12),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(245,194,214,0.35),_transparent_35%),linear-gradient(135deg,_#FFF7FA_0%,_#FFFFFF_55%,_#FFFDF6_100%)] text-slate-900">
    @yield('content')
</body>
</html>