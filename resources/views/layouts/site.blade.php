{{--
    Layout of the public site (spec 026): the bar, the footer and the floating WhatsApp button of
    the proposal in nueva-landing/, on the tokens of docs/design/system.md 2.1.0. A page extends
    it with the stylesheet and script entry of its own: @extends('layouts.site', ['siteAssets' => [...]]).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('site.name'))</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/brand/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/icon-180.png') }}">

    @vite(array_merge(['resources/css/app.css', 'resources/css/site/site.css', 'resources/js/site/core.js'], $siteAssets ?? []))
</head>
<body>
    <div class="opt" data-clinic-schedule="{{ json_encode((object) config('site.contact.schedule', [])) }}">
        <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-100 focus:inline-flex focus:min-h-control focus:items-center focus:rounded-control focus:bg-surface focus:px-4 focus:text-sm focus:font-semibold focus:text-ink">Saltar al contenido</a>

        <x-site.header />

        <main id="contenido" tabindex="-1">
            @yield('content')
        </main>

        <x-site.footer />

        <x-ui.status />
    </div>
</body>
</html>
