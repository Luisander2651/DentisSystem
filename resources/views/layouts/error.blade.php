{{--
    Marco de las páginas de error (403, 404, 405 y 419): la marca, un título, un texto fijo en
    español y el camino de vuelta. No imprime la excepción ni nada de la petición, y no necesita
    sesión: el enlace de vuelta depende solo de si quien la ve está identificado.
--}}
@php
    $signedIn = rescue(fn () => auth('sanctum')->check(), false, false);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') - Dentissa</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-180.png') }}">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-canvas text-ink">
    <main class="mx-auto flex min-h-screen max-w-lg flex-col items-center justify-center px-4 py-10 text-center">
        <x-ui.brand variant="logo" class="w-52" />

        <section class="mt-8 w-full rounded-card border border-line bg-surface p-6 shadow-sm md:p-10">
            <p class="text-min font-bold uppercase tracking-wide text-muted">@yield('title')</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-ink md:text-3xl">@yield('heading')</h1>
            <p class="mt-3 text-sm leading-6 text-muted md:text-base">@yield('message')</p>

            <x-ui.button variant="primary" class="mt-6" :href="$signedIn ? route('dashboard') : route('inicio')">
                {{ $signedIn ? 'Volver a mi inicio' : 'Ir al inicio del sitio' }}
            </x-ui.button>
        </section>
    </main>
</body>
</html>
