{{--
    Spec 027: "Deja tu opinión". A visitor who already came to the clinic leaves an opinion with the
    name to show and the consent to publish both; it arrives hidden and the clinic publishes it from
    "Contenido" (CA1, CA2). A plain form posted to the server, so it works without JavaScript; each
    error sits beside its field and is tied to it (CA3, CA10). The trap field `website` is out of
    sight and out of the keyboard (CA6). After sending, the thanks take the place of the form in a
    status region (CA2). Styles in resources/css/site/pages/opinion.css.
--}}
@extends('layouts.site', ['siteAssets' => ['resources/js/site/pages/opinion.js']])

@section('title', 'Deja tu opinión - Dentissa')
@section('description', 'Cuéntanos cómo fue tu visita a Dentissa: la clínica revisa cada opinión antes de publicarla en el sitio.')
@section('noindex', '1')

@php
    $underline = '<svg viewBox="0 0 300 20" preserveAspectRatio="none" aria-hidden="true"><path d="M3 15 C 60 4, 140 2, 297 11" fill="none" stroke-width="7" stroke-linecap="round"></path></svg>';
    $describedBy = fn (string $field, string $hint = ''): string => trim(($errors->has($field) ? 'op-'.$field.'-err ' : '').$hint);
@endphp

@section('content')
<section class="op-top">
    <div class="wrap op-grid">
        <div class="op-copy">
            <span class="eyebrow rise"><span class="dot"></span>Testimonios</span>
            <h1 class="h-xl rise d1">Deja tu <span class="hl">opinión{!! $underline !!}</span></h1>
            <p class="lead rise d2">Cuéntanos cómo fue tu visita. Leemos cada opinión y la publicamos en el sitio cuando la revisamos.</p>
        </div>

        <div class="card op-card rise d2">
            @if ($throttled ?? false)
                <p class="op-alert" role="alert">Enviaste varias opiniones seguidas. Inténtalo de nuevo en unos minutos.</p>
                <a class="btn btn-ghost" href="{{ route('inicio') }}">Volver al inicio</a>
            @elseif (session('opinion_sent'))
                <div class="op-done" role="status">
                    <h2 class="op-done-h" tabindex="-1" data-op-thanks>¡Gracias por tu opinión!</h2>
                    <p>La recibimos bien: la publicaremos cuando la revisemos.</p>
                    <a class="btn btn-ghost" href="{{ route('inicio') }}">Volver al inicio</a>
                </div>
            @else
                <form class="op-form" method="post" action="{{ route('opinion.store') }}" data-op-form>
                    @csrf

                    @error('form')
                        <p class="op-alert" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="op-field">
                        <label class="op-lbl" for="op-name">Tu nombre</label>
                        <p class="op-hint" id="op-name-hint">Como quieres que aparezca junto a tu opinión.</p>
                        <input class="op-input" id="op-name" name="name" type="text" value="{{ old('name') }}" required minlength="2" maxlength="80" autocomplete="name" aria-describedby="{{ $describedBy('name', 'op-name-hint') }}" @error('name') aria-invalid="true" @enderror>
                        @error('name')<p class="op-err" id="op-name-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="op-field">
                        <label class="op-lbl" for="op-text">Tu opinión</label>
                        <textarea class="op-input op-text" id="op-text" name="opinion" rows="5" required minlength="20" maxlength="255" aria-describedby="{{ $describedBy('opinion', 'op-text-count') }}" @error('opinion') aria-invalid="true" @enderror>{{ old('opinion') }}</textarea>
                        <p class="op-hint" id="op-text-count" data-op-count>Entre 20 y 255 caracteres.</p>
                        @error('opinion')<p class="op-err" id="op-opinion-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="op-field">
                        <label class="op-check">
                            <input type="checkbox" name="consent" value="1" required @checked(old('consent')) aria-describedby="{{ $describedBy('consent') }}" @error('consent') aria-invalid="true" @enderror>
                            <span>Acepto que Dentissa publique mi nombre y mi opinión en su sitio. Consulta el <a href="{{ route('aviso-privacidad') }}">aviso de privacidad</a>.</span>
                        </label>
                        @error('consent')<p class="op-err" id="op-consent-err">{{ $message }}</p>@enderror
                    </div>

                    <div class="op-trap" hidden aria-hidden="true">
                        <label for="op-website">No llenes este campo</label>
                        <input id="op-website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <button class="btn btn-primary op-send" type="submit" data-pressable>Enviar mi opinión</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
