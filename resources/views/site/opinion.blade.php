{{--
    Spec 027: "Deja tu opinión". A visitor leaves an opinion that the clinic reviews before
    publishing it. T002 serves only the notice of the limit; T003 builds the page.
--}}
@extends('layouts.site')

@section('title', 'Deja tu opinión - Dentissa')
@section('description', 'Cuéntanos cómo fue tu visita a Dentissa: la clínica revisa cada opinión antes de publicarla en el sitio.')

@section('content')
    <section class="section">
        <div class="wrap">
            <h1 class="h-lg">Deja tu opinión</h1>
            @if ($throttled ?? false)
                <p role="alert">Enviaste varias opiniones seguidas. Inténtalo de nuevo en unos minutos.</p>
            @endif
        </div>
    </section>
@endsection
