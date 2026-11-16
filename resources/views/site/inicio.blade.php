{{--
    Home of the public site (spec 026), from pages/inicio.blade.php of the proposal in nueva-landing/:
    same composition, with the real content. Promotions, certifications and testimonials come from
    ShowLandingPageController (only visible and current, CA2, CA14); treatments, contact details and
    opening hours from config/site.php (CA7, CA17). Every text goes through Blade's escaped output
    (CA15). Styles in resources/css/site/pages/inicio.css.
--}}
@extends('layouts.site', ['siteAssets' => ['resources/js/site/pages/inicio.js']])

@section('title', config('site.pages./.title'))
@section('description', config('site.pages./.description'))

@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
    $treatments = config('site.treatments');
    $schedule = \App\Core\Http\PublicSiteSchedule::fromConfig()->lines();
    $date = fn (?string $value): string => $value ? \Illuminate\Support\Carbon::parse($value)->locale('es')->isoFormat('DD MMM YYYY') : '';
    $promos = collect($promotions ?? [])->values();
    $first = $promos->first();
    $certs = collect($certifications ?? [])->sortBy('date')->values();
    $tests = collect($testimonials ?? [])->values();
    $initials = fn (string $text): string => \Illuminate\Support\Str::of($text)->explode(' ')->filter(fn ($word) => mb_strlen($word) > 2)->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    $photos = [
        ['consulta.webp', 'Dentista conversando con una paciente en el sillón dental'],
        ['odontopediatria.webp', 'Dentista atendiendo a una niña sonriente con un peluche de diente'],
        ['radiografia.webp', 'Dentista explicando una radiografía digital a un paciente'],
        ['sonrisa.webp', 'Paciente con una sonrisa luminosa'],
        ['recepcion.webp', 'Recepción de la clínica recibiendo a una paciente'],
        ['consultorio.webp', 'Consultorio dental moderno con unidad dental'],
    ];
    $treatmentIcons = [
        '<path d="M12 3c-3 4-6 7-6 11a6 6 0 0 0 12 0c0-4-3-7-6-11z"></path><path d="M9.5 14.5a2.5 2.5 0 0 0 2.5 2.5"></path>',
        '<rect x="2.5" y="8" width="5" height="8" rx="1.5"></rect><rect x="9.5" y="8" width="5" height="8" rx="1.5"></rect><rect x="16.5" y="8" width="5" height="8" rx="1.5"></rect><path d="M1.5 12h21"></path>',
        '<path d="M7 3h10l-1 5H8L7 3z"></path><path d="M8.5 10h7M9 13h6M9.5 16h5M10 19h4M12 8v14"></path>',
        '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"></path>',
        '<circle cx="12" cy="12" r="9"></circle><path d="M8.5 14.5c1.8 2 5.2 2 7 0"></path><circle cx="9" cy="10" r=".6" fill="currentColor"></circle><circle cx="15" cy="10" r=".6" fill="currentColor"></circle>',
    ];
    $star = '<path d="M12 0C13 8 16 11 24 12 16 13 13 16 12 24 11 16 8 13 0 12 8 11 11 8 12 0Z" fill="currentColor"></path>';
    $arrow = '<svg class="arr" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    $underline = '<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg>';
@endphp

@section('content')
<section id="explora" class="vw" data-ref="VwSec" aria-label="Bienvenida a Dentissa">
    <div class="vw-sticky" data-scene>
        <div class="wrap vw-grid">
            <div class="vw-copy" data-ref="VwCopy">
                <span class="eyebrow rise"><span class="dot"></span>Odontología integral · {{ config('site.city') }}</span>
                <h1 class="h-xl rise d1">La mejor tecnología para tu <span class="hl">salud dental{!! $underline !!}</span></h1>
                <p class="lead rise d2 vw-lead">Ofrecemos tratamientos dentales integrales de alta calidad en un ambiente cálido y seguro, para que cada visita se sienta tranquila.</p>
                <div class="rise d3 vw-ctas">
                    <a class="btn btn-primary btn-lg" href="{{ $whatsapp }}">Agenda una Cita{!! $arrow !!}</a>
                    <a class="btn btn-ghost btn-lg" href="{{ route('contacto') }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"></path><circle cx="12" cy="9.5" r="2.5"></circle></svg>Ver Ubicación</a>
                </div>
                <ul class="rise d4 vw-feats">
                    <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="5" width="14" height="14" rx="3"></rect><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"></path></svg><span>Equipamiento moderno</span></li>
                    <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="6"></circle><path d="M9.5 9l1.8 1.8L15 7.2M8.5 14l-1.5 7 5-2.5 5 2.5-1.5-7"></path></svg><span>Especialistas certificados</span></li>
                    <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6l8-3z"></path><path d="M8.5 12l2.5 2.5 4.5-5"></path></svg><span>100% Seguro · Normas COFEPRIS</span></li>
                    <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><circle cx="6" cy="11" r="2"></circle><path d="M7 21c0-3 2.2-6 5-6s5 3 5 6M2.5 19c0-2 1.6-4 3.5-4"></path></svg><span>Para niños y adultos</span></li>
                </ul>
            </div>

            <div class="vw-right" data-ref="VwRight">
                <div class="vw-slot" data-ref="VwSlot" aria-hidden="true"></div>
            </div>
        </div>

        <div class="dk-layer" data-audit="animado" data-ref="DkLayer" role="group" aria-label="Fotos de la clínica Dentissa">
            @foreach ($photos as [$file, $alt])
                <figure class="dk-card" data-i="{{ $loop->index }}"><img src="{{ asset('images/site/'.$file) }}" alt="{{ $alt }}" width="880" height="880" data-origen="generada" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif></figure>
            @endforeach
        </div>

        <div class="dk-end" data-ref="DkEnd">
            <span class="kicker">Bienvenido a Dentissa</span>
            <h2 class="h-lg">Un ambiente cálido y seguro</h2>
            <p class="lead dk-lead">Tratamientos dentales integrales de alta calidad, con especialistas certificados y equipamiento moderno.</p>
            <a class="btn btn-primary btn-lg dk-cta" href="{{ $whatsapp }}">Agenda una Cita{!! $arrow !!}</a>
        </div>

        <span class="vw-hint" data-ref="VwHint"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"></path></svg>Desliza para entrar</span>
    </div>
</section>

<div class="marquee" data-audit="animado" aria-label="Nuestros servicios">
    <div class="marquee-track">
        @foreach ([false, true] as $copy)
            <div class="marquee-item" @if ($copy) aria-hidden="true" @endif>
                @foreach ($treatments as $treatment){{ $treatment['name'] }}<svg width="26" height="26" viewBox="0 0 24 24" aria-hidden="true">{!! $star !!}</svg>@endforeach
            </div>
        @endforeach
    </div>
</div>

<section class="tour" data-ref="Tour" aria-label="Recorrido 3D por nuestros tratamientos">
    <div class="tour-sticky wrap" data-scene>
        <div class="tour-stage" data-bg>
            <div class="plate"></div>
            <img class="tour-still" src="{{ asset('images/site/diente.webp') }}" alt="" width="720" height="720" loading="lazy" data-origen="dibujada">
            <div class="orbits" aria-hidden="true"><div class="orb o1"><i></i></div><div class="orb o2"><i></i></div></div>
            <div class="orbits front" aria-hidden="true"><div class="orb o1"><i></i></div><div class="orb o2"><i></i></div></div>
        </div>
        <div class="tour-copy">
            <span class="kicker">Recorrido 3D</span>
            @foreach (array_slice($treatments, 0, 4) as $treatment)
                <span class="if" data-if="ts{{ $loop->index }}">
                    <div class="tour-step"><h2 class="h-lg">{{ $treatment['name'] }}</h2><p class="lead">{{ $treatment['summary'] }}</p></div>
                </span>
            @endforeach
            <div class="bar" aria-hidden="true"><i class="on"></i><i></i><i></i><i></i></div>
            <span class="scroll-hint"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"></path></svg>Desliza para recorrer los tratamientos</span>
            <a class="btn btn-primary btn-lg" href="{{ $whatsapp }}">Agenda tu valoración{!! $arrow !!}</a>
        </div>
    </div>
</section>

<section class="section" id="servicios">
    <div class="wrap sv-wrap">
        <div class="reveal sv-head">
            <div class="sv-title">
                <span class="kicker">Servicios</span>
                <h2 class="h-lg">Todo lo que tu sonrisa necesita, en un solo lugar</h2>
            </div>
            <p class="lead sv-lead">Tratamientos para niños y adultos, en un ambiente cálido y seguro.</p>
        </div>
        <div class="reveal3d sv-grid">
            @foreach ($treatments as $treatment)
                <article class="card tilt sv-card" data-tilt>
                    <span class="ico z2"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $treatmentIcons[$loop->index] ?? $treatmentIcons[0] !!}</svg></span>
                    <h3 class="h-md z1 sv-name">{{ $treatment['name'] }}</h3>
                    <p class="z1 sv-text">{{ $treatment['summary'] }}</p>
                    <a class="mini z1 sv-link" href="{{ $whatsapp }}" aria-label="Agendar {{ $treatment['name'] }}"><span class="cube"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span>Agendar</a>
                </article>
            @endforeach

            <a class="card tilt sv-ask" href="{{ $whatsapp }}" data-tilt>
                <span class="z1 sv-ask-q">¿No sabes cuál necesitas?</span>
                <span class="z2 sv-ask-a">Agenda una valoración<span class="sv-ask-ic"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M9 7h8v8"></path></svg></span></span>
            </a>
        </div>
    </div>
</section>

<section class="section section-flush" id="promociones">
    <div class="wrap" data-promos>
        @if ($promos->isEmpty())
            <div class="pm-none reveal3d" data-pm-empty>
                <div class="pm-none-art" aria-hidden="true">
                    <span class="rings spin pm-none-ring-1"></span>
                    <span class="rings pm-none-ring-2"></span>
                    <span class="pm-pct"></span>
                    <span class="pm-gt t1"></span>
                    <span class="pm-gt t2"></span>
                    <span class="pm-gt t3"></span>
                </div>
                <div class="pm-none-copy">
                    <span class="kicker">Promociones</span>
                    <h2 class="h-lg">Por ahora no hay <span class="hl">promociones{!! $underline !!}</span> activas</h2>
                    <p class="lead pm-none-lead">Lanzamos descuentos por temporada y a veces tenemos alguno que aún no publicamos. Pregúntanos por WhatsApp.</p>
                    <div class="pm-none-acts">
                        <a class="btn btn-wa btn-lg" href="{{ $whatsapp }}?text={{ rawurlencode('Hola, ¿tienen alguna promoción disponible?') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path></svg>Pregúntanos por WhatsApp</a>
                        <a class="pm-none-link" href="#servicios">Ver tratamientos</a>
                    </div>
                </div>
            </div>
        @else
            <div data-pm-any>
                <div class="coupon reveal3d" data-surface="dark">
                    <div class="pm-in swA" data-pm-card>
                        <div class="pm-left">
                            <span class="rings spin pm-ring-1" aria-hidden="true"></span>
                            <span class="rings pm-ring-2" aria-hidden="true"></span>
                            <span class="rings pm-ring-3" aria-hidden="true"></span>
                            <div class="pm-pct-box">
                                <div class="pct"><span data-pm="pct">{{ $first['discount_percentage'] }}</span><small>%</small></div>
                                <div class="pm-pct-label">de descuento</div>
                            </div>
                        </div>
                        <div class="pm-right">
                            <div class="pm-top"><span class="kicker pm-kicker">Promociones</span>@if ($promos->count() > 1)<span class="pm-of" data-pm="of">1 de {{ $promos->count() }} activas</span>@endif</div>
                            <h2 class="h-lg pm-title" data-pm="title">{{ $first['name'] }}</h2>
                            <p class="pm-desc" data-pm="desc" @if (! $first['description']) hidden @endif>{{ $first['description'] }}</p>
                            <p class="pm-dates"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="3"></rect><path d="M3.5 10h17M8 3v4M16 3v4"></path></svg><span>Válido del <span data-pm="from">{{ $date($first['start_date']) }}</span> al <span data-pm="to">{{ $date($first['end_date']) }}</span></span></p>
                            <div class="pm-cd">
                                <div class="pm-cd-t"><span>Tiempo restante</span><em data-pm="left">&nbsp;</em></div>
                                <div class="pm-bar"><i data-pm="bar"></i></div>
                            </div>
                            <div class="pm-actions">
                                <a class="btn btn-light btn-lg" href="{{ $whatsapp }}">Aprovechar promoción{!! $arrow !!}</a>
                                <a class="pm-more-link" href="{{ $whatsapp }}">Pregunta por otras promociones</a>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($promos->count() > 1)
                    <div class="pm-more reveal" data-pm-more>
                        <div class="pm-more-h"><span class="kicker">Otras promociones activas</span><small>Toca un cupón para verlo en grande</small></div>
                        <div class="pm-tix">
                            @foreach ($promos as $promo)
                                <button class="tix" type="button" data-pressable data-promo data-pct="{{ $promo['discount_percentage'] }}" data-title="{{ $promo['name'] }}" data-desc="{{ $promo['description'] }}" data-from="{{ $date($promo['start_date']) }}" data-to="{{ $date($promo['end_date']) }}" data-start="{{ $promo['start_date'] }}" data-end="{{ $promo['end_date'] }}" aria-label="Ver promoción {{ $promo['name'] }}, {{ $promo['discount_percentage'] }}% de descuento" @if ($loop->first) hidden @endif><span class="tix-pct">{{ $promo['discount_percentage'] }}<small>%</small></span><span class="tix-body"><b>{{ $promo['name'] }}</b><small>Hasta el {{ $date($promo['end_date']) }} · <span data-left>&nbsp;</span></small></span></button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>

@if ($certs->isNotEmpty())
    <section class="section section-flush cf-section" id="certificaciones" data-certs>
        <div class="wrap cf-head reveal">
            <div>
                <span class="kicker">Certificaciones</span>
                <h2 class="h-lg">Respaldos que <span class="hl">avalan{!! $underline !!}</span> nuestra atención</h2>
                <p class="lead">Estándares, reconocimientos y validaciones institucionales. Toca un marco para verlo completo.</p>
            </div>
            <span class="cf-count"><b>{{ $certs->count() }}</b>{{ $certs->count() === 1 ? 'respaldo' : 'respaldos' }}</span>
        </div>
        <div class="cf-row{{ $certs->count() <= 3 ? ' few' : '' }}{{ $certs->count() === 1 ? ' one' : '' }}">
            @foreach ($certs as $cert)
                @php
                    $institution = $cert['description'] ?: config('site.name');
                    $year = $cert['date'] ? \Illuminate\Support\Carbon::parse($cert['date'])->year : '';
                @endphp
                <article class="cf">
                    <span class="cf-pin" aria-hidden="true"></span>
                    <button class="cf-frame" type="button" data-pressable data-cf data-title="{{ $cert['name'] }}" data-inst="{{ $institution }}" data-year="{{ $year }}" data-date="{{ $date($cert['date']) }}" data-ini="{{ $initials($institution) }}" data-img="{{ $cert['image_url'] }}" aria-label="Ver {{ $cert['name'] }}, {{ $institution }}">
                        <span class="cf-mat">@if ($cert['image_url'])<img src="{{ $cert['image_url'] }}" alt="" loading="lazy" data-origen="propia">@else<span class="cf-seal"><b>{{ $initials($institution) }}</b></span>@endif</span>
                        <span class="cf-zoom" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="6.5"></circle><path d="M16 16l4.5 4.5M11 8.5v5M8.5 11h5"></path></svg></span>
                    </button>
                    <div class="cf-meta">
                        <span class="cf-year">{{ $year }}</span>
                        <h3>{{ $cert['name'] }}</h3>
                        <p>{{ $institution }} · {{ $date($cert['date']) }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="lb" data-lb hidden>
            <div class="lb-in" role="dialog" aria-modal="true" aria-labelledby="lb-title">
                <button class="lb-x" type="button" data-lb-close aria-label="Cerrar"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"></path></svg></button>
                <div class="lb-img">
                    <img data-lb-img src="" alt="" hidden data-origen="propia">
                    <span class="cf-seal" data-lb-seal><b data-lb="ini"></b></span>
                </div>
                <div class="lb-cap">
                    <span class="cf-year" data-lb="year"></span>
                    <h3 class="h-md lb-title" id="lb-title" data-lb="title"></h3>
                    <p class="lb-text" data-lb="inst"></p>
                    <p class="lb-text lb-date">Fecha: <span data-lb="date"></span></p>
                </div>
                <x-ui.status placement="dialog" />
            </div>
        </div>
    </section>
@endif

<section class="section section-flush" id="testimonios">
    <div class="wrap">
        @if ($tests->isEmpty())
            <div class="ts-empty reveal">
                <span class="ts-avg" aria-hidden="true">“</span>
                <div><h2>¿Ya nos visitaste?</h2><p>Cuéntanos cómo fue tu experiencia: tu opinión ayuda a otros pacientes a conocernos.</p></div>
                <a class="btn btn-primary" href="{{ $whatsapp }}">Dejar mi opinión</a>
            </div>
        @else
            <div class="ts" data-ts>
                <div class="ts-side reveal">
                    <span class="kicker">Testimonios</span>
                    <h2 class="h-lg">Lo que dicen nuestros <span class="hl">pacientes{!! $underline !!}</span></h2>
                    <p class="ts-count">{{ $tests->count() }} {{ $tests->count() === 1 ? 'opinión de un paciente' : 'opiniones de pacientes' }}</p>
                    @if ($tests->count() > 1)
                        <div class="ts-nav">
                            <button class="ts-btn" type="button" data-pressable data-ts-prev aria-label="Testimonio anterior"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"></path></svg></button>
                            <button class="ts-btn ts-pause" type="button" data-pressable data-ts-pause aria-pressed="false" aria-label="Pausar los testimonios"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M9 6v12M15 6v12"></path></svg></button>
                            <span class="ts-num"><b data-ts-pos>01</b> / {{ str_pad((string) $tests->count(), 2, '0', STR_PAD_LEFT) }}</span>
                            <button class="ts-btn" type="button" data-pressable data-ts-next aria-label="Siguiente testimonio"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></button>
                        </div>
                    @endif
                </div>
                <figure class="ts-card reveal3d" aria-live="polite">
                    <svg class="ts-q" width="96" height="96" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 18V12c0-4 2.4-6.6 6-7v3c-1.8.5-2.8 1.8-3 4h3v6H4zm10 0V12c0-4 2.4-6.6 6-7v3c-1.8.5-2.8 1.8-3 4h3v6h-6z"></path></svg>
                    @foreach ($tests as $testimonial)
                        <div class="ts-body swA" data-ts-slide @unless ($loop->first) hidden @endunless>
                            <blockquote class="ts-text">“{{ $testimonial['description'] }}”</blockquote>
                            <figcaption class="ts-who"><span class="ts-av" aria-hidden="true">{{ mb_strtoupper(mb_substr($testimonial['author'], 0, 1)) }}</span><span><b>{{ $testimonial['author'] }}</b></span></figcaption>
                        </div>
                    @endforeach
                    @if ($tests->count() > 1)
                        <div class="ts-dots">
                            @foreach ($tests as $testimonial)
                                <button class="ts-dot{{ $loop->first ? ' on' : '' }}" type="button" data-ts-dot aria-label="Ver testimonio {{ $loop->iteration }} de {{ $tests->count() }}"><span><i></i></span></button>
                            @endforeach
                        </div>
                    @endif
                </figure>
            </div>
            @if ($tests->count() >= 4)
                <div class="ts-marq" data-audit="animado" aria-hidden="true">
                    <div class="ts-track">
                        @foreach ($tests->concat($tests) as $testimonial)
                            <span class="ts-chip"><em>“{{ \Illuminate\Support\Str::limit($testimonial['description'], 44) }}”</em>{{ $testimonial['author'] }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>

<section class="section section-flush" id="faq">
    <div class="wrap fq">
        <div class="fq-left reveal">
            <span class="kicker">Preguntas frecuentes</span>
            <h2 class="fq-title">Resolvemos tus <span class="hl">dudas{!! $underline !!}</span></h2>
            <p class="lead fq-lead">Lo que más nos preguntan antes de la primera cita, contestado sin rodeos.</p>
            <figure class="fq-photo"><img src="{{ asset('images/site/consulta.webp') }}" alt="Dentista platicando con una paciente sentada en el sillón dental" width="1300" height="1300" loading="lazy" data-origen="generada"></figure>
            <div class="fq-more">
                <small>¿Tu pregunta no está aquí?</small>
                <a href="{{ $whatsapp }}">Escríbenos por WhatsApp <span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <div class="fq-list reveal">
            <div class="fq-item on">
                <button class="fq-q" type="button" data-pressable data-action="fq0"><span class="fq-n">01</span><span class="fq-t">¿Cada cuánto debo hacerme una limpieza?</span><span class="fq-pm" aria-hidden="true"></span></button>
                <div class="fq-a" id="fqa0"><div><div class="fq-in">
                    <p>Recomendamos una limpieza dental profesional <b>cada 6 meses</b>. Así previenes el sarro, detectas a tiempo posibles caries y mantienes tus encías sanas.</p>
                    <div class="fq-viz" aria-hidden="true">
                        <div class="fq-vh"><span>Tu calendario ideal</span><em>2 visitas al año</em></div>
                        <div class="cal"><div class="cal-arc"></div>@foreach (['E', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'] as $month)<span @if (in_array($loop->index, [0, 6], true)) class="v" @endif style="--i:{{ $loop->index }}"><i></i>{{ $month }}</span>@endforeach</div>
                    </div>
                </div></div></div>
            </div>

            <div class="fq-item on">
                <button class="fq-q" type="button" data-pressable data-action="fq1"><span class="fq-n">02</span><span class="fq-t">¿Qué tipos de ortodoncia ofrecen?</span><span class="fq-pm" aria-hidden="true"></span></button>
                <div class="fq-a" id="fqa1"><div><div class="fq-in">
                    <p>Tenemos <b>tres opciones</b> para que elijas la que mejor va con tu estilo de vida y presupuesto.</p>
                    <div class="fq-viz fq-opts">
                        <div class="fq-opt" style="--i:0"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><rect x="3" y="8" width="5" height="8" rx="1.5"></rect><rect x="16" y="8" width="5" height="8" rx="1.5"></rect><path d="M1 12h22"></path></svg><b>Brackets metálicos</b>Los más resistentes</div>
                        <div class="fq-opt" style="--i:1"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 15.6 7.1 18.2 8 12.7 4 8.8 9.5 8z"></path></svg><b>Brackets estéticos</b>Zafiro o cerámica</div>
                        <div class="fq-opt" style="--i:2"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M3 10c3-4 15-4 18 0-1 5-4 8-9 8s-8-3-9-8z" stroke-dasharray="3 2.5"></path></svg><b>Alineadores invisibles</b>Casi imperceptibles</div>
                    </div>
                </div></div></div>
            </div>

            <div class="fq-item on">
                <button class="fq-q" type="button" data-pressable data-action="fq2"><span class="fq-n">03</span><span class="fq-t">¿Duele ponerse un implante?</span><span class="fq-pm" aria-hidden="true"></span></button>
                <div class="fq-a" id="fqa2"><div><div class="fq-in">
                    <p>El procedimiento se realiza con <b>anestesia local</b>. Las molestias posteriores son mínimas y se controlan con analgésicos recetados.</p>
                    <div class="fq-viz" aria-hidden="true">
                        <div class="fq-vh"><span>Molestia esperada</span><em>Mínima</em></div>
                        <div class="meter"><b></b></div>
                        <div class="meter-l"><span>Nada</span><span>Mucha</span></div>
                        <div class="fq-tags"><span>Anestesia local</span><span>Analgésicos recetados</span><span>Seguimiento incluido</span></div>
                    </div>
                </div></div></div>
            </div>

            <div class="fq-item on">
                <button class="fq-q" type="button" data-pressable data-action="fq3"><span class="fq-n">04</span><span class="fq-t">¿Aceptan seguros de gastos médicos?</span><span class="fq-pm" aria-hidden="true"></span></button>
                <div class="fq-a" id="fqa3"><div><div class="fq-in">
                    <p>Trabajamos con <b>reembolso</b> para la mayoría de las aseguradoras de gastos médicos mayores y te entregamos factura e informe médico.</p>
                    <div class="fq-viz" aria-hidden="true">
                        <div class="fq-vh"><span>Así funciona</span><em>3 pasos</em></div>
                        <div class="steps"><span><em style="--i:0">1</em>Tu tratamiento</span><span><em style="--i:1">2</em>Factura e informe médico</span><span><em style="--i:2">3</em>Reembolso de tu aseguradora</span></div>
                    </div>
                </div></div></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-flush" id="ubicacion">
    <div class="wrap">
        <div class="vis reveal3d">
            <div class="vis-st closed" data-vis>
                <span class="vis-pill"><i aria-hidden="true"></i><span data-vis="text">Horario de atención</span></span>
                <b class="vis-big"><span data-vis="sub">{{ $schedule[0]['days'] ?? '' }}, {{ $schedule[0]['hours'] ?? '' }}</span></b>
                <small>Hoy: <em><span data-vis="hours">consulta el horario</span></em></small>
            </div>
            <div class="vis-c">
                <small class="vis-k">Visítanos</small>
                <b class="vis-mid">{{ $contact['address'] }}</b>
                <a class="vis-link" href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener noreferrer">Cómo llegar <span aria-hidden="true">↗</span><span class="sr-only"> (se abre en otra pestaña)</span></a>
            </div>
            <div class="vis-c">
                <small class="vis-k">Llámanos</small>
                <a class="vis-tel" href="{{ $phoneHref }}">{{ $contact['phone'] }}</a>
                <a class="btn btn-wa vis-wa" href="{{ $whatsapp }}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path></svg>WhatsApp</a>
            </div>
        </div>
        <a class="vis-more" href="{{ route('contacto') }}">Ver horarios y mapa completo <span aria-hidden="true">→</span></a>
    </div>
</section>

<section class="cta-section">
    <div class="wrap">
        <div class="cta3d reveal3d" data-surface="dark">
            <div class="cta-grid" data-bg></div>
            <div class="cta-copy">
                <span class="kicker cta-kicker">Agenda hoy</span>
                <h2 class="h-lg cta-title">Tu próxima sonrisa está a <span class="hl">un mensaje{!! $underline !!}</span> de distancia</h2>
                <p class="cta-sub">Escríbenos por WhatsApp y te respondemos al momento para agendar tu cita.</p>
                <div class="cta-actions">
                    <a class="btn btn-wa btn-lg" href="{{ $whatsapp }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg>Escríbenos por WhatsApp</a>
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
                    <div class="ph-head"><span class="ph-av"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><path d="M7 3c-2.5 0-4 2-4 4.5 0 2 .7 3.3 1.3 5 .6 1.8.8 4 1.4 6.2.3 1.2.8 2.3 1.8 2.3 1.4 0 1.6-2 2-3.6.3-1.3.9-2.4 2.5-2.4s2.2 1.1 2.5 2.4c.4 1.6.6 3.6 2 3.6 1 0 1.5-1.1 1.8-2.3.6-2.2.8-4.4 1.4-6.2.6-1.7 1.3-3 1.3-5C21 5 19.5 3 17 3c-2 0-3 1-5 1S9 3 7 3z"></path></svg></span><span class="ph-name"><b>Dentissa</b><br><span class="ph-on"><i></i>en línea</span></span></div>
                    <div class="ph-body">
                        <div class="msg out m1">Hola, quiero agendar una limpieza.</div>
                        <div class="msg in m2">¡Hola! Claro, ¿qué día te acomoda?</div>
                        <div class="msg out m3">¿El sábado en la mañana?</div>
                        <div class="msg in m4">Perfecto, te esperamos el sábado.</div>
                        <div class="typing"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
