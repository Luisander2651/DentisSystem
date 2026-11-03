@extends('layouts.landing')

@section('title', 'Términos y Condiciones - Dentissa')
@section('description', 'Condiciones de uso del sitio de Dentissa: cuentas de paciente, solicitud y cancelación de citas, alcance de la información publicada y responsabilidades.')

@php
    $controller = config('legal.controller');
    $link = 'font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4';
@endphp

@section('content')
<x-landing.legal-document
    title="Términos y Condiciones"
    :version="config('legal.terms.version')"
    :updated-at="config('legal.terms.updated_at')"
    :sections="[
        'aceptacion' => 'Aceptación',
        'servicios' => 'Qué ofrece el sitio',
        'cuenta' => 'Tu cuenta',
        'citas' => 'Citas',
        'alcance' => 'Alcance de la información',
        'uso' => 'Uso adecuado',
        'propiedad' => 'Propiedad intelectual',
        'responsabilidad' => 'Responsabilidad',
        'privacidad' => 'Privacidad',
        'cambios' => 'Cambios a estos términos',
        'jurisdiccion' => 'Ley aplicable y jurisdicción',
        'contacto' => 'Contacto',
    ]"
>
    <x-slot:intro>
        <p>Estos términos regulan el uso del sitio de la clínica dental Dentissa, operada por <strong class="text-ink">{{ $controller['name'] }}</strong>. Léelos con atención antes de usarlo.</p>
    </x-slot:intro>

    <x-landing.legal-section id="aceptacion" title="Aceptación">
        <p>Al navegar por el sitio o crear una cuenta, aceptas estos términos. Si no estás de acuerdo con ellos, te pedimos no usar el sitio.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="servicios" title="Qué ofrece el sitio">
        <p>El sitio te permite conocer la clínica, sus tratamientos y su equipo, y crear una cuenta de paciente con la que, a medida que se habiliten, podrás consultar tus citas y tu información. La clínica puede cambiar, suspender o retirar cualquiera de estas funciones.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="cuenta" title="Tu cuenta">
        <ul class="list-disc space-y-2 pl-5">
            <li>Los datos que registres deben ser verdaderos, estar completos y mantenerse al día.</li>
            <li>Tu cuenta es personal. Cuida tu contraseña y no la compartas: eres responsable de lo que se haga con tu cuenta.</li>
            <li>Si crees que alguien más usó tu cuenta, cambia tu contraseña y avísanos de inmediato.</li>
            <li>La clínica puede suspender una cuenta que se use en contra de estos términos.</li>
        </ul>
    </x-landing.legal-section>

    <x-landing.legal-section id="citas" title="Citas">
        <ul class="list-disc space-y-2 pl-5">
            <li>Toda cita depende de la disponibilidad de la agenda y queda firme cuando la clínica la confirma.</li>
            <li>Si no puedes asistir, avísanos con la mayor anticipación posible para cancelarla o reprogramarla.</li>
            <li>La clínica puede reprogramar una cita por causas de fuerza mayor o de organización, y te avisará por los medios de contacto que registraste.</li>
            <li>El costo de cada tratamiento se acuerda con la clínica antes de realizarlo.</li>
        </ul>
    </x-landing.legal-section>

    <x-landing.legal-section id="alcance" title="Alcance de la información">
        <p>La información publicada en el sitio es orientativa y no sustituye una consulta. Solo un profesional de la salud bucal puede darte un diagnóstico y un tratamiento después de valorarte.</p>
        <p>Ante una urgencia, acude de inmediato a la clínica o al servicio de salud más cercano.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="uso" title="Uso adecuado">
        <p>Al usar el sitio te comprometes a no:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li>Entrar o intentar entrar a cuentas o información de otras personas.</li>
            <li>Registrar datos falsos o de otra persona sin su autorización.</li>
            <li>Interferir con el funcionamiento del sitio o usarlo para fines ilícitos.</li>
        </ul>
    </x-landing.legal-section>

    <x-landing.legal-section id="propiedad" title="Propiedad intelectual">
        <p>Los textos, imágenes, logotipos y el diseño del sitio pertenecen a la clínica o a quienes le dieron permiso de usarlos. No puedes copiarlos ni usarlos con fines comerciales sin autorización por escrito.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="responsabilidad" title="Responsabilidad">
        <p>Procuramos que el sitio esté disponible y que su información sea correcta, pero no garantizamos que funcione sin interrupciones ni errores. La clínica no responde por daños causados por un uso del sitio contrario a estos términos ni por fallas ajenas a su control.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="privacidad" title="Privacidad">
        <p>El tratamiento de tus datos personales se rige por nuestro <a href="{{ route('aviso-privacidad') }}" class="{{ $link }}">Aviso de Privacidad</a>.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="cambios" title="Cambios a estos términos">
        <p>Podemos actualizar estos términos. Publicaremos cada versión en esta página con su fecha de actualización; si sigues usando el sitio después de un cambio, aceptas la nueva versión.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="jurisdiccion" title="Ley aplicable y jurisdicción">
        <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Para cualquier controversia, las partes se someten a los tribunales competentes de {{ $controller['jurisdiction'] }}, y renuncian a cualquier otro fuero que pudiera corresponderles.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="contacto" title="Contacto">
        <p>Si tienes dudas sobre estos términos, escríbenos a <strong class="text-ink">{{ $controller['privacy_email'] }}</strong> o visita la página de <a href="{{ route('contacto') }}" class="{{ $link }}">Contacto y Ubicación</a>.</p>
    </x-landing.legal-section>
</x-landing.legal-document>
@endsection
