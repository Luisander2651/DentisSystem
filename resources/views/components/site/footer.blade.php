{{--
    Footer of the public site (spec 026) and the floating WhatsApp button. Every contact value and
    the opening hours come from config/site.php (real or provisional, CA7); the legal pages are
    linked by their routes (022 CA6). Styles in resources/css/site/components.css.
--}}
@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
    $schedule = \App\Core\Http\PublicSiteSchedule::fromConfig()->lines();
@endphp
<footer class="ft" data-surface="dark">
    <div class="wrap ft-grid">
        <div class="ft-col ft-brand">
            <a href="{{ route('inicio') }}" class="logo ft-logo">
                <img src="{{ asset('images/brand/icon-64.png') }}" alt="Logo de Dentissa" width="40" height="40" data-origen="propia" class="size-10 shrink-0 rounded-control">
                Dentissa
            </a>
            <p class="ft-text">Odontología integral con tecnología avanzada, especialistas dedicados y un ambiente cómodo para toda la familia.</p>
            <div class="ft-social">
                <a class="social" href="{{ $contact['facebook'] }}" aria-label="Facebook"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15V3z"></path></svg></a>
                <a class="social" href="{{ $contact['instagram'] }}" aria-label="Instagram"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.3" cy="6.7" r=".8" fill="currentColor"></circle></svg></a>
                <a class="social" href="{{ $whatsapp }}" aria-label="WhatsApp"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path></svg></a>
            </div>
        </div>

        <nav class="ft-col" aria-label="Pie de página">
            <h2 class="ft-title">Navegación</h2>
            <a href="{{ route('inicio') }}">Inicio</a>
            <a href="{{ route('acerca') }}">Acerca de Nosotros</a>
            <a href="{{ route('galeria') }}">Galería de Fotos</a>
            <a href="{{ route('contacto') }}">Contacto y Ubicación</a>
        </nav>

        <div class="ft-col ft-text">
            <h2 class="ft-title">Contacto</h2>
            <span>{{ $contact['address'] }}</span>
            <a href="{{ $phoneHref }}">{{ $contact['phone'] }}</a>
            <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
        </div>

        <div class="ft-col ft-text">
            <h2 class="ft-title">Horarios</h2>
            @foreach ($schedule as $line)
                <span>{{ $line['days'] }}: {{ $line['hours'] }}</span>
            @endforeach
        </div>
    </div>

    <div class="wrap ft-bottom">
        <span>© {{ now()->year }} Dentissa. Todos los derechos reservados.</span>
        <span class="ft-legal">
            <a href="{{ route('aviso-privacidad') }}">Aviso de Privacidad</a>
            <a href="{{ route('terminos') }}">Términos y Condiciones</a>
        </span>
    </div>
</footer>

<a class="wa-float" href="{{ $whatsapp }}" aria-label="Agenda por WhatsApp"><svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg></a>
