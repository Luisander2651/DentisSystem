{{--
    Top bar of the public site (spec 026): the clinic's logo (016 CA15), the pages with the
    current one marked, the sign-in link, the WhatsApp call to action and, below 920 px, the
    folding menu. Styles in resources/css/site/components.css.
--}}
@php
    $whatsapp = 'https://wa.me/'.config('site.contact.whatsapp');
    $pages = [
        ['route' => 'inicio', 'label' => 'Inicio'],
        ['route' => 'acerca', 'label' => 'Acerca de Nosotros'],
        ['route' => 'galeria', 'label' => 'Galería'],
        ['route' => 'contacto', 'label' => 'Contacto'],
    ];
@endphp
<header class="hdr">
    <div class="hdr-in">
        <a href="{{ route('inicio') }}" class="logo">
            <img src="{{ asset('images/brand/icon-64.png') }}" alt="Logo de Dentissa" width="40" height="40" data-origen="propia" class="size-10 shrink-0 rounded-control">
            <span class="logo-txt">Dentissa<small>Odontología integral</small></span>
        </a>

        <nav class="nav-links" aria-label="Principal">
            @foreach ($pages as $page)
                <a href="{{ route($page['route']) }}" @if (request()->routeIs($page['route'])) aria-current="page" @endif>{{ $page['label'] }}</a>
            @endforeach
        </nav>

        <div class="hdr-cta">
            <a class="hdr-login" href="{{ route('login') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
                Iniciar Sesión
            </a>
            <a class="btn-hdr" href="{{ $whatsapp }}">
                <span class="wa-dot"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg></span>
                Agendar cita
            </a>
        </div>

        <details class="menu-m">
            <summary aria-label="Abrir menú"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 8h16M4 16h16"></path></svg></summary>
            <div class="menu-panel">
                @foreach ($pages as $page)
                    <a href="{{ route($page['route']) }}" @if (request()->routeIs($page['route'])) aria-current="page" @endif>{{ $page['label'] }}</a>
                @endforeach
                <a href="{{ route('login') }}">Iniciar Sesión</a>
                <a class="btn btn-wa menu-wa" href="{{ $whatsapp }}">Escríbenos por WhatsApp</a>
            </div>
        </details>
    </div>
</header>
