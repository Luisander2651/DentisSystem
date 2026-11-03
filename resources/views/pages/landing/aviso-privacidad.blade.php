@extends('layouts.landing')

@section('title', 'Aviso de Privacidad - Dentissa')
@section('description', 'Qué datos personales trata Dentissa, para qué los usa, con quién los comparte y cómo ejercer tus derechos de acceso, rectificación, cancelación y oposición.')

@php
    $controller = config('legal.controller');
    $link = 'inline-flex min-h-control items-center font-semibold text-ink underline decoration-primary decoration-2 underline-offset-4';
@endphp

@section('content')
<x-landing.legal-document
    title="Aviso de Privacidad"
    :version="config('legal.privacy.version')"
    :updated-at="config('legal.privacy.updated_at')"
    :sections="[
        'responsable' => 'Quién es el responsable de tus datos',
        'datos' => 'Qué datos personales tratamos',
        'finalidades' => 'Para qué usamos tus datos',
        'negativa' => 'Cómo negarte a los usos secundarios',
        'proveedores' => 'Con quién compartimos tus datos',
        'derechos' => 'Tus derechos de acceso, rectificación, cancelación y oposición',
        'revocacion' => 'Cómo retirar tu consentimiento o limitar el uso de tus datos',
        'cookies' => 'Cookies del sitio',
        'seguridad' => 'Cómo protegemos tus datos',
        'cambios' => 'Cambios a este aviso',
    ]"
>
    <x-slot:intro>
        <p>Este aviso explica cómo tratamos los datos personales de las personas que visitan este sitio, se registran como pacientes o reciben atención en la clínica, de acuerdo con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>
    </x-slot:intro>

    <x-landing.legal-section id="responsable" title="Quién es el responsable de tus datos">
        <p><strong class="text-ink">{{ $controller['name'] }}</strong>, que opera la clínica dental Dentissa, con domicilio en {{ $controller['address'] }}, es responsable del tratamiento de tus datos personales.</p>
        <p>Para cualquier asunto relacionado con tus datos puedes escribir a <strong class="text-ink">{{ $controller['privacy_email'] }}</strong>.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="datos" title="Qué datos personales tratamos">
        <p>Según cómo te relaciones con la clínica, podemos tratar los siguientes datos:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li><strong class="text-ink">Identificación y contacto:</strong> nombre, apellidos, correo electrónico, teléfono, dirección y un contacto de emergencia.</li>
            <li><strong class="text-ink">Cuenta:</strong> tu correo y tu contraseña, que guardamos cifrada y nadie de la clínica puede leer.</li>
            <li><strong class="text-ink">Citas:</strong> fecha, hora, tratamiento, motivo de la consulta y el código QR de cada cita, con el que se registra tu entrada a la clínica.</li>
        </ul>
        <p>También tratamos <strong class="text-ink">datos personales sensibles</strong>, que la ley protege de forma especial:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li>Datos de salud: alergias, tipo de sangre, medicamentos que tomas y fecha de tu última visita al dentista.</li>
            <li>Tu expediente clínico: síntomas, diagnósticos, procedimientos realizados, observaciones, recomendaciones y recetas.</li>
        </ul>
        <p>Solo tratamos tus datos sensibles con tu consentimiento expreso y por escrito, y únicamente para atenderte.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="finalidades" title="Para qué usamos tus datos">
        <p>Usamos tus datos para estas finalidades, necesarias para darte el servicio que solicitas:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li>Crear y administrar tu cuenta de paciente.</li>
            <li>Agendar, confirmar, reprogramar y cancelar tus citas, incluidos los avisos por WhatsApp y correo electrónico.</li>
            <li>Integrar y actualizar tu expediente clínico, darte seguimiento y emitir tus recetas.</li>
            <li>Registrar tu entrada y salida de la clínica con el código QR de tu cita.</li>
            <li>Cumplir las obligaciones legales y sanitarias que aplican a la clínica.</li>
        </ul>
        <p>Como finalidad secundaria, que no es necesaria para atenderte, podemos enviarte información sobre promociones y servicios de la clínica.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="negativa" title="Cómo negarte a los usos secundarios">
        <p>Si no quieres que usemos tus datos para la finalidad secundaria, escríbenos a {{ $controller['privacy_email'] }} en cualquier momento. Negarte no afecta tu atención en la clínica.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="proveedores" title="Con quién compartimos tus datos">
        <p>Para operar el servicio, algunos proveedores tratan datos por cuenta de la clínica, solo para lo que les encargamos:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li><strong class="text-ink">Twilio:</strong> envía los mensajes de WhatsApp para confirmar tus citas (tu nombre, tu teléfono y los datos de la cita).</li>
            <li><strong class="text-ink">Brevo:</strong> envía los correos electrónicos, como el de recuperación de contraseña.</li>
            <li><strong class="text-ink">Cloudflare:</strong> protege el sitio y guarda las imágenes que publica la clínica y los códigos QR de las citas.</li>
            <li><strong class="text-ink">DigitalOcean:</strong> aloja los servidores donde funciona el sistema.</li>
        </ul>
        <p>Algunos de estos proveedores pueden tratar los datos fuera de México. No vendemos tus datos ni los transferimos a terceros para sus propios fines, salvo en los casos en que la ley lo permite sin tu consentimiento o cuando una autoridad competente lo requiera.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="derechos" title="Tus derechos de acceso, rectificación, cancelación y oposición">
        <p>Tienes derecho a conocer qué datos tenemos de ti y cómo los usamos (acceso), a corregirlos si son inexactos o están incompletos (rectificación), a pedir que los eliminemos (cancelación) y a oponerte a su uso para fines específicos (oposición).</p>
        <p>Para ejercer cualquiera de estos derechos, envía tu solicitud a {{ $controller['privacy_email'] }} con:</p>
        <ul class="list-disc space-y-2 pl-5">
            <li>Tu nombre y un medio para responderte.</li>
            <li>Un documento que acredite tu identidad o, en su caso, la de tu representante.</li>
            <li>La descripción clara de los datos y del derecho que quieres ejercer.</li>
        </ul>
        <p>Te responderemos en los plazos que establece la ley. Ten en cuenta que la clínica debe conservar el expediente clínico durante el tiempo que exigen las normas sanitarias, aunque pidas su cancelación.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="revocacion" title="Cómo retirar tu consentimiento o limitar el uso de tus datos">
        <p>Puedes retirar en cualquier momento el consentimiento que nos diste, o pedirnos que limitemos el uso o la divulgación de tus datos, con una solicitud a {{ $controller['privacy_email'] }} que cumpla los mismos requisitos del apartado anterior.</p>
        <p>Si retiras tu consentimiento para tratar tus datos de salud, es posible que no podamos seguir atendiéndote.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="cookies" title="Cookies del sitio">
        <p>El sitio usa solo las cookies necesarias para funcionar: una que mantiene tu sesión abierta mientras usas tu cuenta y otras que protegen los formularios contra envíos no autorizados. Se borran al cerrar sesión o al caducar.</p>
        <p>No usamos cookies de publicidad ni de medición de visitas.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="seguridad" title="Cómo protegemos tus datos">
        <p>Aplicamos medidas administrativas, técnicas y físicas para proteger tus datos contra daño, pérdida, alteración o acceso no autorizado.</p>
    </x-landing.legal-section>

    <x-landing.legal-section id="cambios" title="Cambios a este aviso">
        <p>Podemos actualizar este aviso por cambios en la ley, en nuestros servicios o en la forma en que tratamos tus datos. Publicaremos cada versión en esta página con su fecha de actualización.</p>
        <p>El uso del sitio también se rige por los Términos y Condiciones.</p>
        <a href="{{ route('terminos') }}" class="{{ $link }}">Leer los Términos y Condiciones</a>
    </x-landing.legal-section>
</x-landing.legal-document>
@endsection
