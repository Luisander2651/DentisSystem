{{--
    "Acerca de nosotros" of the public site (spec 026), from pages/nosotros.blade.php of the proposal
    in nueva-landing/: same composition, with the inline styles turned into classes of
    resources/css/site/pages/nosotros.css and every colour a token (CA1). The WhatsApp link, the
    address and the opening hours come from config/site.php (CA7).
--}}
@extends('layouts.site', ['siteAssets' => ['resources/js/site/pages/nosotros.js']])

@section('title', config('site.pages./acerca-de-nosotros.title'))
@section('description', config('site.pages./acerca-de-nosotros.description'))

@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $schedule = collect(\App\Core\Http\PublicSiteSchedule::fromConfig()->lines());
    $saturday = $schedule->first(fn (array $line): bool => str_contains($line['days'], 'ábado'));
    $saturdayReply = $saturday && $saturday['hours'] !== 'Cerrado'
        ? 'Claro, los sábados abrimos de '.rtrim($saturday['hours'], '.').'.'
        : 'Los sábados no abrimos, pero te esperamos entre semana.';
    $arrow = '<svg class="arr" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    $underline = '<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg>';
    $star = '<path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path>';
    $specialties = [
        ['Ortodoncia', '<rect x="2.5" y="8" width="5" height="8" rx="1.5"></rect><rect x="9.5" y="8" width="5" height="8" rx="1.5"></rect><rect x="16.5" y="8" width="5" height="8" rx="1.5"></rect><path d="M1.5 12h21"></path>'],
        ['Odontopediatría', '<circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.5c1.8 2 5.2 2 7 0"></path>'],
        ['Implantología', '<path d="M7 3h10l-1 5H8L7 3z"></path><path d="M8.5 10h7M9 13h6M9.5 16h5M12 8v14"></path>'],
        ['Estética dental', '<path d="M12 2l1.8 5.6L19.5 9l-5.7 1.6L12 16l-1.8-5.4L4.5 9l5.7-1.4L12 2z"></path>'],
    ];
    $milestones = [
        ['2016', 'Nace Dentissa', 'Abrimos con un solo consultorio.', '<path d="M4 21V9l8-6 8 6v12M9 21v-6h6v6"></path>'],
        ['2020', 'Crecemos', 'Ampliamos a tres unidades dentales, con rayos X digitales y especialistas de tiempo completo.', '<path d="M12 5v14M5 12h14"></path>'],
        ['2026', 'Odontología digital', 'Referente regional en odontología digital: agenda automatizada, expedientes integrados y tratamientos guiados por computadora.', '<rect x="5" y="5" width="14" height="14" rx="3"></rect><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"></path>'],
    ];
    $team = [
        ['SM', 'Dra. Sofía Mendoza', 'Una sonrisa sana empieza por sentirte escuchado desde que te sientas en el sillón.'],
        ['AR', 'Dr. Alejandro Ríos', 'Te explico cada paso del tratamiento, para que nunca haya sorpresas.'],
        ['VE', 'Dra. Valeria Esparza', 'Mi meta es que cada niño salga del consultorio con ganas de volver.'],
    ];
    $values = [
        ['Empatía', 'Escuchamos y entendemos a cada paciente.', '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"></path>'],
        ['Higiene y seguridad', 'Protocolos estrictos en cada procedimiento.', '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path>'],
        ['Honestidad', 'Diagnósticos claros y sin sorpresas.', '<circle cx="12" cy="12" r="9"></circle><path d="M8 12l3 3 5-6"></path>'],
        ['Excelencia', 'Cuidamos cada detalle del tratamiento.', '<circle cx="12" cy="9" r="6"></circle><path d="M8.5 14l-1.5 7 5-2.5 5 2.5-1.5-7"></path>'],
        ['Innovación', 'Tecnología al servicio de tu salud.', '<rect x="5" y="5" width="14" height="14" rx="3"></rect><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"></path>'],
        ['Compromiso', 'Acompañamos a las familias a largo plazo.', '<path d="M4 21V10l8-6 8 6v11"></path><circle cx="12" cy="13" r="2.5"></circle><path d="M8 21c0-2.2 1.8-4 4-4s4 1.8 4 4"></path>'],
    ];
@endphp

@section('content')
<section class="ab-hero">
    <div class="wrap ab-hero-grid">
        <div class="ab-hero-copy">
            <span class="eyebrow rise"><span class="dot"></span>Acerca de Nosotros</span>
            <h1 class="h-xl rise d1">Definimos la excelencia en <span class="hl">odontología integral{!! $underline !!}</span></h1>
            <p class="lead rise d2 ab-lead">Más de 10 años al servicio de nuestra comunidad, con especialistas en ortodoncia, odontopediatría, implantología y estética dental, enfocados en un diagnóstico preciso y un tratamiento hecho a tu medida.</p>
            <a class="btn btn-primary btn-lg rise d3" href="{{ $whatsapp }}">Agenda una Cita{!! $arrow !!}</a>
        </div>
        <div class="stack" data-parallax>
            <span class="rings spin hide-m" aria-hidden="true"></span>
            <div class="stack-in">
                <div class="stat stat-dark si d1">
                    <div class="stat-n"><span class="count c10"></span><small>+</small></div>
                    <div class="stat-l">años cuidando sonrisas</div>
                </div>
                <div class="stat stat-light si d3">
                    <div class="stat-n"><span class="count c3"></span></div>
                    <div class="stat-l">unidades dentales</div>
                </div>
                <div class="stat stat-soft si d4">
                    <div class="stat-n"><span class="count c4"></span></div>
                    <div class="stat-l">especialidades</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section ab-mission">
    <div class="wrap ab-mission-in">
        <span class="kicker reveal">Nuestra misión</span>
        <p class="mission reveal">Combinar un trato cálido y personal con <span class="uline">tecnología dental moderna</span>, para que cada visita se sienta cómoda, tranquila y de confianza.</p>
    </div>
</section>

<section class="ab-band">
    <div class="wrap ab-band-in">
        <div class="reveal ab-head ab-head-narrow">
            <span class="kicker">Especialidades</span>
            <h2 class="h-lg">Un equipo, cuatro especialidades</h2>
        </div>
        <ul class="reveal3d ab-pills">
            @foreach ($specialties as [$name, $icon])
                <li class="pill"><span class="ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg></span>{{ $name }}</li>
            @endforeach
        </ul>
    </div>
</section>

<section class="section">
    <div class="wrap ab-story">
        <div class="reveal ab-head">
            <span class="kicker">Nuestra historia</span>
            <h2 class="h-lg">De un consultorio a referente en odontología digital</h2>
        </div>
        <div class="tl">
            <div class="tl-line" aria-hidden="true"><div class="tl-fill"></div></div>
            <ol class="tl-list">
            @foreach ($milestones as [$year, $title, $text, $icon])
                <li class="reveal3d tl-item">
                    <span class="tl-node @if ($loop->last) tl-node-now @endif" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span>
                    <article class="card tilt tl-card @if ($loop->last) tl-card-now @endif" data-tilt>
                        <span class="tl-year z2">{{ $year }}</span>
                        <h3 class="h-md z1">{{ $title }}</h3>
                        <p class="z1 ab-muted">{{ $text }}</p>
                    </article>
                </li>
            @endforeach
            </ol>
        </div>
    </div>
</section>

<section class="section section-flush">
    <div class="wrap ab-block">
        <div class="reveal ab-team-head">
            <div class="ab-head ab-head-wide">
                <span class="kicker">Nuestro equipo</span>
                <h2 class="h-lg">Las manos detrás de tu sonrisa</h2>
            </div>
            <span class="hint"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 15.5-6.2M21 12a9 9 0 0 1-15.5 6.2"></path><path d="M18.5 2v4h-4M5.5 22v-4h4"></path></svg>Pasa el cursor para girar cada tarjeta</span>
        </div>
        <div class="reveal3d ab-team">
            @foreach ($team as [$initials, $name, $quote])
                <div class="flip">
                    <div class="flip-in">
                        <article class="face front">
                            <div class="av av-{{ $loop->index }}" aria-hidden="true"><div class="av-orb"><i></i></div><div class="av-orb b"><i></i></div><div class="av-core">{{ $initials }}</div></div>
                            <div><h3 class="h-md">{{ $name }}</h3><p class="sr-only">“{{ $quote }}”</p></div>
                        </article>
                        <figure class="face back" data-surface="dark" aria-hidden="true">
                            <span class="kicker face-kicker">Dentissa</span>
                            <blockquote class="face-quote">“{{ $quote }}”</blockquote>
                            <figcaption class="face-by">{{ $name }}</figcaption>
                        </figure>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="section section-flush">
    <div class="wrap ab-block">
        <div class="reveal ab-head ab-head-wide">
            <span class="kicker">Nuestros valores</span>
            <h2 class="h-lg">Lo que nos mueve cada día</h2>
        </div>
        <div class="reveal3d ab-values">
            @foreach ($values as [$title, $text, $icon])
                <article class="val tilt" data-tilt><span class="ico z2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg></span><h3 class="h-md z1">{{ $title }}</h3><p class="z1">{{ $text }}</p></article>
            @endforeach
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="wrap">
        <div class="cta3d reveal3d" data-surface="dark">
            <div class="cta-grid" aria-hidden="true"></div>
            <div class="cta-copy">
                <span class="kicker cta-kicker">Visítanos</span>
                <h2 class="h-lg cta-title">Conócenos <span class="hl">en persona{!! $underline !!}</span></h2>
                <p class="cta-sub">Agenda tu cita por WhatsApp o visítanos en {{ $contact['address'] }}.</p>
                <div class="cta-actions">
                    <a class="btn btn-wa btn-lg" href="{{ $whatsapp }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg>Agenda una Cita</a>
                    <a class="btn btn-call btn-lg" href="{{ route('contacto') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"></path><circle cx="12" cy="9.5" r="2.5"></circle></svg>Ver Ubicación</a>
                </div>
                <div class="cta-meta">
                    @foreach ($schedule as $line)
                        @continue($line['hours'] === 'Cerrado')
                        <span>@if ($loop->first)<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>@endif{{ $line['days'] }} {{ $line['hours'] }}</span>
                    @endforeach
                </div>
            </div>
            <div class="phone-wrap" aria-hidden="true">
                <div class="ph-ghost g2"></div>
                <div class="ph-ghost g1"></div>
                <svg class="tw tw-1" width="26" height="26" viewBox="0 0 24 24">{!! $star !!}</svg>
                <svg class="tw tw-2" width="16" height="16" viewBox="0 0 24 24">{!! $star !!}</svg>
                <svg class="tw tw-3" width="20" height="20" viewBox="0 0 24 24">{!! $star !!}</svg>
                <div class="phone">
                    <div class="ph-head"><span class="ph-av"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M7 3c-2.5 0-4 2-4 4.5 0 2 .7 3.3 1.3 5 .6 1.8.8 4 1.4 6.2.3 1.2.8 2.3 1.8 2.3 1.4 0 1.6-2 2-3.6.3-1.3.9-2.4 2.5-2.4s2.2 1.1 2.5 2.4c.4 1.6.6 3.6 2 3.6 1 0 1.5-1.1 1.8-2.3.6-2.2.8-4.4 1.4-6.2.6-1.7 1.3-3 1.3-5C21 5 19.5 3 17 3c-2 0-3 1-5 1S9 3 7 3z"></path></svg></span><span class="ph-name"><b>Dentissa</b><br><span class="ph-on"><i></i>en línea</span></span></div>
                    <div class="ph-body">
                        <div class="msg out m1">Hola, ¿dónde se encuentran?</div>
                        <div class="msg in m2">En {{ $contact['address'] }}.</div>
                        <div class="msg out m3">¿Puedo ir el sábado?</div>
                        <div class="msg in m4">{{ $saturdayReply }}</div>
                        <div class="typing"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
