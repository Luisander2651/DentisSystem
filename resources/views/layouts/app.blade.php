<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dentissa')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="w-full h-screen">
    @yield('content')
</body>
</html>