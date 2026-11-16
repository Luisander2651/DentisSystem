{{--
    Top bar of the public site (spec 026): the clinic's logo (016 CA15), the pages with the
    current one marked, the sign-in link, the WhatsApp call to action and, below 920 px, the phone
    menu redesigned by the user (T040): a <details> that opens a full-screen sheet with the pages,
    the treatments (WhatsApp with a written message), the coupon of the home (T042, @stack), the
    sign-in link, the networks and a dock with the schedule, "Agendar cita" and "Llamar". It sits
    beside the bar, not inside it, because the bar's blur would trap a fixed sheet; without
    JavaScript it opens without animation. core.js animates it (T041). Styles in components.css.
--}}
@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
    $schedule = collect(\App\Core\Http\PublicSiteSchedule::fromConfig()->lines())->map(fn (array $line): string => $line['days'].' '.$line['hours'])->implode(' · ');
    $ask = fn (string $what): string => $whatsapp.'?text='.rawurlencode("Hola, me interesa {$what}. ¿Qué horarios tienen disponibles?");
    $menuIcons = [
        'Limpieza y Prevención' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3c-2.5 0-4 2-4 4.5 0 2 .7 3.3 1.3 5 .6 1.8.8 4 1.4 6.2.3 1.2.8 2.3 1.8 2.3 1.4 0 1.6-2 2-3.6.3-1.3.9-2.4 2.5-2.4s2.2 1.1 2.5 2.4c.4 1.6.6 3.6 2 3.6 1 0 1.5-1.1 1.8-2.3.6-2.2.8-4.4 1.4-6.2.6-1.7 1.3-3 1.3-5C21 5 19.5 3 17 3c-2 0-3 1-5 1S9 3 7 3z"></path><path d="M18.5 1.5v3M17 3h3"></path></svg>',
        'Ortodoncia' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3c-2.5 0-4 2-4 4.5 0 2 .7 3.3 1.3 5 .6 1.8.8 4 1.4 6.2.3 1.2.8 2.3 1.8 2.3 1.4 0 1.6-2 2-3.6.3-1.3.9-2.4 2.5-2.4s2.2 1.1 2.5 2.4c.4 1.6.6 3.6 2 3.6 1 0 1.5-1.1 1.8-2.3.6-2.2.8-4.4 1.4-6.2.6-1.7 1.3-3 1.3-5C21 5 19.5 3 17 3c-2 0-3 1-5 1S9 3 7 3z"></path><path d="M4 9.5h16"></path><rect x="7" y="8" width="3" height="3" rx=".6"></rect><rect x="14" y="8" width="3" height="3" rx=".6"></rect></svg>',
        'Implantes Dentales' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 3h10l-1 5H8z"></path><path d="M9 8l.6 13h4.8L15 8"></path><path d="M9.2 11.5h5.6M9.4 14.5h5.2M9.6 17.5h4.8"></path></svg>',
        'Blanqueamiento Dental' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"></path><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"></path></svg>',
        'Odontopediatría' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.5c1.8 2 5.2 2 7 0"></path><path d="M9 9.5h.01M15 9.5h.01"></path></svg>',
    ];
    $menuTreatments = collect(config('site.treatments'))
        ->map(fn (array $treatment): array => ['name' => $treatment['name'], 'href' => $ask(mb_strtolower($treatment['name'])), 'icon' => $menuIcons[$treatment['name']] ?? $menuIcons['Limpieza y Prevención']])
        ->push(['name' => 'Valoración general', 'href' => $ask('una valoración general'), 'icon' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="2.5"></rect><path d="M9 4V2.8h6V4"></path><path d="M9 12.5l2 2 4-4.5"></path></svg>']);
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

    </div>

    <details class="menu-m">
        <summary aria-label="Abrir menú" aria-expanded="false"><span></span><span></span><span></span></summary>
        <div class="menu-panel mn-sheet" id="menu-movil">
            <div class="mn-scroll">
                <span class="mn-label mn-in" style="--i: 0">Páginas</span>
                <nav class="mn-links" aria-label="Menú">
                    @foreach ($pages as $page)
                        <a @class(['mn-a', 'mn-in', 'on' => request()->routeIs($page['route'])]) href="{{ route($page['route']) }}" @if (request()->routeIs($page['route'])) aria-current="page" @endif style="--i: {{ $loop->index }}" data-menu-close>{{ $page['label'] }}</a>
                    @endforeach
                </nav>
                <span class="mn-label mn-in" style="--i: 4">Tratamientos</span>
                <div class="mn-grid">
                    @foreach ($menuTreatments as $treatment)
                        <a class="mn-tile mn-in" href="{{ $treatment['href'] }}" style="--i: {{ $loop->index + 4 }}"><span class="mn-ic" style="--i: {{ $loop->index }}">{!! $treatment['icon'] !!}</span><span>{{ $treatment['name'] }}</span></a>
                    @endforeach
                </div>
                @stack('menu-coupon')
                <div class="mn-row mn-in" style="--i: 11">
                    <a class="mn-login" href="{{ route('login') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"></path></svg>Iniciar sesión</a>
                    <span class="mn-soc">
                        <a href="{{ $contact['instagram'] }}" aria-label="Instagram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.3" cy="6.7" r=".8" fill="currentColor"></circle></svg></a>
                        <a href="{{ $contact['facebook'] }}" aria-label="Facebook"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15V3z"></path></svg></a>
                    </span>
                </div>
            </div>
            <div class="mn-dock">
                <span class="mn-st" data-menu-status><i aria-hidden="true"></i><span data-menu-status-text>{{ $schedule }}</span></span>
                <div class="mn-acts">
                    <a class="btn btn-wa" href="{{ $whatsapp }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg>Agendar cita</a>
                    <a class="btn mn-call" href="{{ $phoneHref }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"></path></svg>Llamar</a>
                </div>
            </div>
        </div>
    </details>
</header>
