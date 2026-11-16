{{--
    Contact page of the public site (spec 026), from pages/contacto.blade.php of the proposal in
    nueva-landing/: same composition, with the inline styles turned into classes of
    resources/css/site/pages/contacto.css and every colour a token (CA1). The phone, the email, the
    networks, the address, the map links and the weekly hours come from config/site.php (CA7). The
    form only composes a WhatsApp message in the browser (CA6): it has no address to post to nor a
    CSRF token and it is hidden until JavaScript runs; the direct WhatsApp link is there for
    everyone (A35). The treatments of the motives are those of the list of services (CA17).
--}}
@extends('layouts.site', ['siteAssets' => ['resources/js/site/pages/contacto.js']])

@section('title', config('site.pages./contacto.title'))
@section('description', config('site.pages./contacto.description'))

@php
    $contact = config('site.contact');
    $whatsapp = 'https://wa.me/'.$contact['whatsapp'];
    $phoneHref = 'tel:'.preg_replace('/[^\d+]/', '', $contact['phone']);
    $wazeUrl = 'https://waze.com/ul?q='.rawurlencode($contact['address']).'&navigate=yes';
    $instagramHandle = '@'.trim((string) parse_url($contact['instagram'], PHP_URL_PATH), '/');
    $scheduleLine = collect(\App\Core\Http\PublicSiteSchedule::fromConfig()->lines())->map(fn (array $line): string => $line['days'].' '.$line['hours'])->implode(' · ');
    $clock = function (string $time): string {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return sprintf('%d:%02d %s', $hour % 12 === 0 ? 12 : $hour % 12, $minute, $hour < 12 ? 'a. m.' : 'p. m.');
    };
    $percent = fn (float $value): string => rtrim(rtrim(number_format(max(0, min(100, $value)), 2, '.', ''), '0'), '.').'%';
    $minutes = function (string $time): int {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $hour * 60 + $minute;
    };
    $rulerFrom = 8 * 60;
    $rulerSpan = 12 * 60;
    $week = collect([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'])
        ->map(function (string $name, int $isoDay) use ($contact, $clock, $percent, $minutes, $rulerFrom, $rulerSpan): array {
            $hours = $contact['schedule'][$isoDay] ?? null;

            return [
                'name' => $name,
                'day' => $isoDay % 7,
                'hours' => $hours ? $clock($hours[0]).' – '.$clock($hours[1]) : 'Cerrado',
                'bar' => $hours ? '--from: '.$percent(($minutes($hours[0]) - $rulerFrom) / $rulerSpan * 100).'; --span: '.$percent(($minutes($hours[1]) - $minutes($hours[0])) / $rulerSpan * 100).'; --i: '.($isoDay - 1) : null,
            ];
        })
        ->values();
    $motives = collect(config('site.treatments'))
        ->map(fn (array $treatment): array => ['label' => $treatment['name'], 'message' => 'Hola, me gustaría agendar una cita de '.mb_strtolower($treatment['name']).'. ¿Qué horarios tienen disponibles?'])
        ->push(['label' => 'Valoración general', 'message' => 'Hola, me gustaría agendar una valoración general. ¿Qué horarios tienen disponibles?'])
        ->push(['label' => 'Otro', 'message' => 'Hola, tengo una pregunta: ']);
    $arrow = '<svg class="arr" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>';
    $underline = '<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg>';
    $whatsappIcon = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.4L3 21z"></path><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.4-2-1-1 .8a4 4 0 0 1-2.1-2.1l.8-1-1-2L9 9.5z"></path></svg>';
    $streets = [['h', '0', '430px', '40px'], ['h', '0', '200px', '18px'], ['h', '0', '680px', '18px'], ['v', '400px', '0', '22px'], ['v', '640px', '0', '18px'], ['v', '180px', '0', '14px']];
    $parks = [['120px', '140px', '220px', '170px'], ['560px', '600px', '180px', '140px']];
    $buildings = [
        ['440px', '240px', '90px', '80px', '70px'], ['540px', '330px', '80px', '80px', '40px'], ['220px', '490px', '150px', '70px', '50px'],
        ['680px', '230px', '110px', '100px', '90px'], ['220px', '590px', '80px', '70px', '30px'], ['680px', '490px', '100px', '80px', '60px'],
    ];
@endphp

@section('content')
<section class="ct-top" id="escribenos">
    <div class="wrap ct-hero">
        <div class="ct-copy">
            <span class="eyebrow rise"><span class="dot"></span>Contacto</span>
            <h1 class="h-xl rise d1">Hablemos de tu <span class="hl">sonrisa{!! $underline !!}</span></h1>
            <p class="lead rise d2 ct-lead">Escríbenos para resolver tus dudas o agendar tu consulta. Elige el motivo y te dejamos el mensaje listo.</p>
            <span class="status closed rise d3" data-status="full"><i aria-hidden="true"></i><span data-status-text>{{ $scheduleLine }}</span></span>
            <div class="ct-call rise d3">
                <small>¿Prefieres llamar?</small>
                <div class="ct-call-row">
                    <a class="ct-tel" href="{{ $phoneHref }}">{{ $contact['phone'] }}</a>
                    <button class="ct-copy-btn" type="button" data-copy="{{ $contact['phone'] }}" aria-label="Copiar el teléfono"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2.5"></rect><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"></path></svg><span data-copy-label>Copiar</span></button>
                </div>
                <a class="ct-mail" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
            </div>
            <div class="ct-soc rise d4">
                <small>Síguenos</small>
                <a href="{{ $contact['instagram'] }}" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.3" cy="6.7" r=".8" fill="currentColor"></circle></svg>{{ $instagramHandle }}<span class="sr-only"> (se abre en otra pestaña)</span></a>
                <a href="{{ $contact['facebook'] }}" target="_blank" rel="noopener noreferrer"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15V3z"></path></svg>Facebook<span class="sr-only"> (se abre en otra pestaña)</span></a>
            </div>
        </div>

        <div class="card ct-comp rise d2">
            <div class="ct-comp-h">
                <span class="ico">{!! $whatsappIcon !!}</span>
                <div><b>Escríbenos por WhatsApp</b><small>Respuesta inmediata</small></div>
            </div>
            <form class="ct-form" data-wa-form data-wa-number="{{ $contact['whatsapp'] }}" novalidate>
                <fieldset class="ct-fs">
                    <legend>¿Para qué nos escribes?</legend>
                    <div class="ct-chips">
                        @foreach ($motives as $motive)
                            <button @class(['ct-chip', 'on' => $loop->first]) type="button" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-motivo="{{ $motive['message'] }}">{{ $motive['label'] }}</button>
                        @endforeach
                    </div>
                </fieldset>
                <div class="ct-field">
                    <label class="ct-lbl" for="ct-msg">Tu mensaje</label>
                    <textarea class="ct-msg" id="ct-msg" name="text" rows="4" aria-describedby="ct-err">{{ $motives->first()['message'] }}</textarea>
                    <span class="ct-err" id="ct-err" data-wa-error hidden>Escribe tu mensaje antes de enviarlo.</span>
                </div>
                <button class="btn btn-wa btn-lg ct-send" type="submit" data-pressable>Enviar por WhatsApp{!! $arrow !!}</button>
                <small class="ct-note">Se abrirá WhatsApp con tu mensaje; puedes cambiarlo antes de enviarlo.</small>
            </form>
            <div class="ct-direct">
                <a class="btn btn-wa btn-lg ct-send" href="{{ $whatsapp }}">Abrir WhatsApp{!! $arrow !!}</a>
                <small class="ct-note">Escríbenos tu duda o el tratamiento que te interesa.</small>
            </div>
        </div>
    </div>
</section>

<section class="ct-loc" id="ubicacion">
    <div class="wrap ct-sec-h reveal3d">
        <span class="kicker">Ubicación y horarios</span>
        <h2 class="h-lg">Encuéntranos</h2>
    </div>
    <div class="wrap ct-grid">
        <div class="map reveal3d" data-parallax role="img" aria-label="Mapa ilustrado de la ubicación de Dentissa en {{ $contact['address'] }}">
            <div class="plane" aria-hidden="true">
                @foreach ($parks as [$x, $y, $width, $depth])
                    <div class="park" style="--x: {{ $x }}; --y: {{ $y }}; --w: {{ $width }}; --d: {{ $depth }}"></div>
                @endforeach
                @foreach ($streets as [$axis, $x, $y, $size])
                    <div class="street {{ $axis }}" style="--x: {{ $x }}; --y: {{ $y }}; --s: {{ $size }}"></div>
                @endforeach
                @foreach ($buildings as [$x, $y, $width, $depth, $height])
                    <div class="bld" style="--x: {{ $x }}; --y: {{ $y }}; --w: {{ $width }}; --d: {{ $depth }}; --h: {{ $height }}"><div class="top"></div><div class="fs"></div><div class="rs"></div></div>
                @endforeach
                <div class="bld us" style="--x: 440px; --y: 490px; --w: 100px; --d: 90px; --h: 46px"><div class="top"></div><div class="fs"></div><div class="rs"></div></div>
                <div class="pulse" style="--x: 490px; --y: 535px"></div>
                <div class="pinw" style="--x: 490px; --y: 535px"><svg class="pin" viewBox="0 0 24 26"><path class="pin-body" d="M12 25s-9-7.6-9-14.5A9 9 0 0 1 21 10.5C21 17.4 12 25 12 25z"></path><path class="pin-mark" d="M9 6.8c-1 0-1.6.8-1.6 1.8 0 .8.3 1.3.5 2 .2.7.3 1.6.6 2.5.1.5.3.9.7.9.6 0 .6-.8.8-1.4.1-.5.4-1 1-1s.9.5 1 1c.2.6.2 1.4.8 1.4.4 0 .6-.4.7-.9.3-.9.4-1.8.6-2.5.2-.7.5-1.2.5-2 0-1-.6-1.8-1.6-1.8-.8 0-1.2.4-2 .4s-1.2-.4-2-.4z"></path></svg></div>
            </div>
            <div class="map-label">
                <span class="map-name"><b>Dentissa</b><br><small>{{ $contact['address'] }}</small></span>
                <div class="map-acts">
                    <a class="btn btn-primary" href="{{ $contact['maps_url'] }}" target="_blank" rel="noopener noreferrer">Google Maps<span class="sr-only"> (se abre en otra pestaña)</span></a>
                    <a class="btn btn-ghost" href="{{ $wazeUrl }}" target="_blank" rel="noopener noreferrer">Waze<span class="sr-only"> (se abre en otra pestaña)</span></a>
                    <button class="btn btn-ghost" type="button" data-copy="{{ $contact['address'] }}" data-copied="Dirección copiada"><span data-copy-label>Copiar dirección</span></button>
                </div>
            </div>
        </div>

        <article class="card wk reveal3d" aria-label="Horario semanal">
            <div class="wk-h"><span class="kicker">Horario</span><span class="status closed" data-status="short"><i aria-hidden="true"></i><span data-status-text>Horario</span></span></div>
            <div class="wk-ruler" aria-hidden="true"><span></span><span class="wk-ticks"><span>8</span><span>10</span><span>12</span><span>14</span><span>16</span><span>18</span><span>20</span></span><span></span></div>
            @foreach ($week as $day)
                <div @class(['wk-row', 'closed' => ! $day['bar']]) data-day="{{ $day['day'] }}">
                    <span class="wk-d">{{ $day['name'] }}<span class="today-tag" hidden>Hoy</span></span>
                    <span class="wk-track" aria-hidden="true">@if ($day['bar'])<i class="wk-bar" style="{{ $day['bar'] }}"></i>@endif<i class="wk-now" hidden></i></span>
                    <b class="wk-t">{{ $day['hours'] }}</b>
                </div>
            @endforeach
            <p class="wk-tz">Horario de {{ config('site.city') }}.</p>
        </article>
    </div>
</section>

<section class="ct-visit">
    <div class="wrap">
        <div class="fv reveal3d">
            <div class="fv-copy">
                <span class="kicker">Tu primera visita</span>
                <h2 class="h-lg">Así de <span class="hl">sencillo{!! $underline !!}</span></h2>
                <ol class="fv-steps">
                    <li><span class="fv-n" aria-hidden="true">1</span><h3>Escríbenos</h3><p>Por WhatsApp o por teléfono, con el motivo de tu consulta.</p></li>
                    <li><span class="fv-n" aria-hidden="true">2</span><h3>Te confirmamos</h3><p>Acordamos contigo la fecha y la hora que mejor te acomoden.</p></li>
                    <li><span class="fv-n" aria-hidden="true">3</span><h3>Te esperamos</h3><p>En {{ $contact['address'] }}; nuestro equipo te recibe en recepción.</p></li>
                </ol>
                <a class="btn btn-primary btn-lg" href="#escribenos">Empezar ahora{!! $arrow !!}</a>
            </div>
            <figure class="fv-photo"><img src="{{ asset('images/site/recepcion.webp') }}" alt="Recepción de la clínica Dentissa" width="880" height="880" loading="lazy" data-origen="generada"></figure>
        </div>
    </div>
</section>
@endsection
