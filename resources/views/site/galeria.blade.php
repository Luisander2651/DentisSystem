{{--
    Gallery of the public site (spec 026), from pages/galeria.blade.php of the proposal in
    nueva-landing/: same composition, with the photos the staff publishes in "Contenido" (only the
    visible ones, from ShowGalleryPageController, CA5 and CA14). The tour of the clinic has one
    composition per number of images (design.md → Pantallas): none → a notice with WhatsApp, one or
    two → a still row, three or more → the horizontal tour. Styles in resources/css/site/pages/galeria.css.
--}}
@extends('layouts.site', ['siteAssets' => ['resources/js/site/pages/galeria.js']])

@section('title', config('site.pages./galeria.title'))
@section('description', config('site.pages./galeria.description'))

@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
    $schedule = \App\Core\Http\PublicSiteSchedule::fromConfig()->lines();
    $images = collect($images ?? [])->values();
    $treatments = collect(config('site.treatments'))->keyBy('name');
    $beforeAfter = collect(['white' => 'Blanqueamiento Dental', 'braces' => 'Ortodoncia', 'implant' => 'Implantes Dentales'])
        ->filter(fn (string $name): bool => $treatments->has($name))
        ->map(fn (string $name): array => $treatments->get($name));
    $firstTreatment = $beforeAfter->first();
    $composition = match (true) {
        $images->isEmpty() => 'vacia',
        $images->count() < 3 => 'fila',
        default => 'recorrido',
    };
    $firstPhotos = 3;
    $placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    $askPhotos = $whatsapp.'?text='.rawurlencode('Hola, me gustaría conocer las instalaciones de Dentissa.');
    $underline = '<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg>';
    $star = '<path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path>';
    $whatsappIcon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg>';
@endphp

@section('content')
<section class="gl-hero">
    <div class="wrap gl-hero-copy">
        <span class="eyebrow rise"><span class="dot"></span>Galería Dentissa</span>
        <h1 class="h-xl rise d1 gl-title">Nuestros Casos y <span class="hl">Consultorios{!! $underline !!}</span></h1>
        <p class="lead rise d2 gl-lead">Un recorrido visual por nuestras instalaciones y por el antes y después de nuestros tratamientos.</p>
    </div>
    <div class="wrap hero-frames" aria-hidden="true">
        <div class="hf a"><div class="hf-art"><img src="{{ asset('images/site/consultorio.webp') }}" alt="" width="880" height="880" decoding="async" data-origen="generada"></div><span class="hf-label">Instalaciones</span></div>
        <div class="hf b"><div class="hf-art hf-art-dark"><img src="{{ asset('images/site/consulta.webp') }}" alt="" width="880" height="880" decoding="async" data-origen="generada"></div><span class="hf-label">Casos</span></div>
        <div class="hf c"><div class="hf-art hf-art-alt"><img src="{{ asset('images/site/sonrisa.webp') }}" alt="" width="880" height="880" decoding="async" data-origen="generada"></div><span class="hf-label">Antes y después</span></div>
    </div>
</section>

@if ($composition === 'recorrido')
    <section class="hz" data-gallery="recorrido" data-ref="Hz" aria-labelledby="gl-instalaciones">
        <div class="hz-sticky">
            <div class="wrap hz-head">
                <div class="gl-head">
                    <span class="kicker">Instalaciones</span>
                    <h2 class="h-lg" id="gl-instalaciones">Conoce nuestro consultorio</h2>
                </div>
                <div class="hz-hint">
                    <span>Desliza para recorrer</span>
                    <div class="hz-bar" aria-hidden="true"><i></i></div>
                </div>
            </div>
            <div class="hz-view" tabindex="0" role="region" aria-label="Fotos del consultorio">
                <div class="hz-track" data-ref="Track" data-audit="animado">
                    @foreach ($images as $image)
                        <figure class="frame">
                            <div class="frame-art">
                                @if ($loop->index < $firstPhotos)
                                    <img src="{{ $image['url'] }}" alt="{{ $image['description'] }}" width="400" height="380" loading="lazy" decoding="async" data-origen="propia">
                                @else
                                    <img src="{{ $placeholder }}" data-src="{{ $image['url'] }}" alt="{{ $image['description'] }}" width="400" height="380" decoding="async" data-origen="propia">
                                    <noscript><img src="{{ $image['url'] }}" alt="{{ $image['description'] }}" width="400" height="380" loading="lazy" decoding="async" data-origen="propia"></noscript>
                                @endif
                            </div>
                            <figcaption aria-hidden="true"><b>{{ $image['description'] }}</b><span>Instalaciones</span></figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@else
    <section class="section gl-rooms" data-gallery="{{ $composition }}" aria-labelledby="gl-instalaciones">
        <div class="wrap gl-rooms-in">
            <div class="gl-head">
                <span class="kicker">Instalaciones</span>
                <h2 class="h-lg" id="gl-instalaciones">Conoce nuestro consultorio</h2>
            </div>
            @if ($composition === 'fila')
                <div class="gl-row">
                    @foreach ($images as $image)
                        <figure class="frame">
                            <div class="frame-art"><img src="{{ $image['url'] }}" alt="{{ $image['description'] }}" width="400" height="380" loading="lazy" decoding="async" data-origen="propia"></div>
                            <figcaption aria-hidden="true"><b>{{ $image['description'] }}</b><span>Instalaciones</span></figcaption>
                        </figure>
                    @endforeach
                </div>
            @else
                <div class="gl-empty">
                    <div class="gl-empty-art" aria-hidden="true"><span class="ring-a"></span><span class="ring-b"></span><svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V9l8-6 8 6v12"></path><path d="M9 21v-6h6v6"></path></svg></div>
                    <div class="gl-empty-copy">
                        <h3 class="h-md">Muy pronto añadiremos las fotos</h3>
                        <p class="gl-muted">Estamos preparando un recorrido por nuestras instalaciones. Mientras tanto, pregúntanos lo que quieras saber.</p>
                        <a class="btn btn-wa" href="{{ $askPhotos }}">{!! $whatsappIcon !!}Pregúntanos por WhatsApp</a>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

@if ($beforeAfter->isNotEmpty())
    {{-- Before and after (CA18): an illustrated 3D denture (gallery.js) over a flat drawing that
         stays when the browser cannot draw in 3D; the text of the chosen treatment is the page's. --}}
    <section class="ba-section" data-before-after aria-labelledby="gl-antes-despues">
        <div class="wrap ba-in">
            <div class="ba-head reveal">
                <div class="ba-head-copy">
                    <span class="kicker">Antes y después</span>
                    <h2 class="h-lg" id="gl-antes-despues">Desliza y mira la diferencia</h2>
                </div>
                <div class="seg" role="group" aria-label="Elegir tratamiento">
                    @foreach ($beforeAfter as $mode => $treatment)
                        <button type="button" @class(['seg-b', 'on' => $loop->first]) data-ba="{{ $mode }}" data-ba-summary="{{ $treatment['summary'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $treatment['name'] }}</button>
                    @endforeach
                </div>
            </div>
            <div class="ba reveal3d">
                <div class="ba-2d" aria-hidden="true">
                    <div class="ba-layer">
                        <div class="ba-mouth"></div>
                        <div class="ba-gum up"></div>
                        <div class="ba-gum down"></div>
                        <div class="teeth" data-teeth="before"></div>
                    </div>
                    <div class="ba-layer ba-after" data-ba-after>
                        <div class="ba-mouth"></div>
                        <div class="ba-gum up"></div>
                        <div class="ba-gum down"></div>
                        <div class="teeth" data-teeth="after"></div>
                        <span class="if" data-if="isWhite">
                            <svg class="spark s1" width="34" height="34" viewBox="0 0 24 24"><path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path></svg>
                            <svg class="spark s2" width="22" height="22" viewBox="0 0 24 24"><path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path></svg>
                            <svg class="spark s3" width="26" height="26" viewBox="0 0 24 24"><path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path></svg>
                        </span>
                    </div>
                </div>
                <canvas class="ba-cv" aria-hidden="true"></canvas>
                <span class="ba-label l" aria-hidden="true">Antes</span>
                <span class="ba-label r" aria-hidden="true">Después</span>
                <input class="ba-range" type="range" min="0" max="100" step="1" value="50" aria-label="Comparar antes y después">
                <div class="ba-handle"><span class="ba-knob"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l-6 6 6 6M15 6l6 6-6 6"></path></svg></span></div>
            </div>
            <p class="ba-caption"><span><b data-ba-title>{{ $firstTreatment['name'] }}</b> · <span data-ba-text>{{ $firstTreatment['summary'] }}</span></span><span>Ilustración de referencia. Pronto compartiremos casos reales.</span></p>
        </div>
    </section>
@endif

<section class="cta-section">
    <div class="wrap">
        <div class="cta3d reveal3d" data-surface="dark">
            <div class="cta-grid" aria-hidden="true"></div>
            <div class="cta-copy">
                <span class="kicker cta-kicker">Tu turno</span>
                <h2 class="h-lg cta-title">¿Quieres ver tu propia <span class="hl">transformación{!! $underline !!}</span>?</h2>
                <p class="cta-sub">Agenda una valoración y conoce el tratamiento ideal para tu sonrisa.</p>
                <div class="cta-actions">
                    <a class="btn btn-wa btn-lg" href="{{ $whatsapp }}">{!! $whatsappIcon !!}Agenda una Cita</a>
                    <a class="btn btn-call btn-lg" href="{{ $phoneHref }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"></path></svg>{{ $contact['phone'] }}</a>
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
                    <div class="ph-head"><span class="ph-av"><img src="{{ asset('images/brand/icon-64.png') }}" alt="Logo de Dentissa" width="36" height="36" data-origen="propia"></span><span class="ph-name"><b>Dentissa</b><br><span class="ph-on"><i></i>en línea</span></span></div>
                    <div class="ph-body">
                        <div class="msg out m1">Hola, me interesa un blanqueamiento.</div>
                        <div class="msg in m2">¡Claro! Los resultados se ven en una sola sesión.</div>
                        <div class="msg out m3">¿Puedo agendar una valoración?</div>
                        <div class="msg in m4">Por supuesto, ¿qué día te acomoda?</div>
                        <div class="typing"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
