<?php

/*
|--------------------------------------------------------------------------
| Public site (spec 026)
|--------------------------------------------------------------------------
|
| The data the public site shows: the clinic's contact details, opening hours, treatments and
| the title and description of each page. Change them here or in .env, never in the views.
|
| Every contact value is the real one when its SITE_* variable is set and, until the clinic gives
| it, a coherent provisional value (amendment of CA7, 2026-11-12). `provisional` marks which
| ones still are: they are shown on the pages but never published to search engines.
|
| SITE_SCHEDULE lists open days and hours separated by ";": "1-5 09:00-19:00; 6 09:00-14:00",
| where 1 is Monday and 7 Sunday. A day that is not listed is closed.
|
*/

$provisionalContact = [
    'whatsapp' => '521234567890',
    'phone' => '+52 55 1234 5678',
    'email' => 'contacto@dentissa.com',
    'address' => 'Lázaro Cárdenas, Michoacán',
    'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode('Lázaro Cárdenas Michoacán'),
    'instagram' => 'https://www.instagram.com/dentissa_clinic',
    'facebook' => 'https://www.facebook.com/dentissa.clinic',
    'schedule' => '1-5 09:00-19:00; 6 09:00-14:00',
];

$variables = [
    'whatsapp' => 'SITE_WHATSAPP',
    'phone' => 'SITE_PHONE',
    'email' => 'SITE_EMAIL',
    'address' => 'SITE_ADDRESS',
    'maps_url' => 'SITE_MAPS_URL',
    'instagram' => 'SITE_INSTAGRAM',
    'facebook' => 'SITE_FACEBOOK',
    'schedule' => 'SITE_SCHEDULE',
];

$parseSchedule = static function (string $schedule): array {
    $days = [];

    foreach (array_filter(array_map('trim', explode(';', $schedule))) as $rule) {
        if (! preg_match('/^([1-7])(?:-([1-7]))?\s+(\d{2}:\d{2})-(\d{2}:\d{2})$/', $rule, $match)) {
            continue;
        }

        foreach (range((int) $match[1], (int) ($match[2] ?: $match[1])) as $day) {
            $days[$day] = [$match[3], $match[4]];
        }
    }

    ksort($days);

    return $days;
};

$contact = [];
$provisional = [];

foreach ($provisionalContact as $key => $default) {
    $value = trim((string) env($variables[$key], ''));
    $provisional[$key] = $value === '';
    $contact[$key] = $value === '' ? $default : $value;
}

$contact['schedule'] = $parseSchedule($contact['schedule']);

return [

    'new_landing' => (bool) env('SITE_NEW_LANDING', true),

    'name' => 'Dentissa',

    /* The main phrase of the home, also drawn on the share card (public/images/brand/compartir.png,
       spec 026 CA19). Changing it obliges to run `npm run brand:assets` again with the same text
       in TAGLINE of tests/Browser/make-brand-assets.mjs; PublicSiteSeoTest checks both. */
    'tagline' => 'La mejor tecnología para tu salud dental',

    'url' => 'https://dentissapp.com',

    'timezone' => 'America/Mexico_City',

    /* The colour of the browser bar: the value of --color-primary in resources/css/app.css (A8). A
       meta tag cannot read a CSS variable; PublicSiteSeoTest fails if the two drift apart. */
    'theme_color' => '#d75078',

    'contact' => $contact,

    'provisional' => $provisional,

    /* The city of the clinic (amendment of CA7, 2026-11-15): the label of the welcome names it. */
    'city' => 'Lázaro Cárdenas',

    'treatments' => [
        ['name' => 'Limpieza y Prevención', 'summary' => 'Limpieza profunda con ultrasonido para eliminar el sarro y prevenir caries.'],
        ['name' => 'Ortodoncia', 'summary' => 'Brackets tradicionales o alineadores invisibles, para niños y adultos.'],
        ['name' => 'Implantes Dentales', 'summary' => 'Implantes de titanio para reemplazar las piezas perdidas.'],
        ['name' => 'Blanqueamiento Dental', 'summary' => 'Aclaramiento con láser, con resultados en una sola sesión.'],
        ['name' => 'Odontopediatría', 'summary' => 'Atención especializada para los más pequeños de la casa.'],
    ],

    'indexable' => [
        '/',
        '/acerca-de-nosotros',
        '/galeria',
        '/contacto',
        '/aviso-de-privacidad',
        '/terminos-y-condiciones',
    ],

    'pages' => [
        '/' => [
            'title' => 'Dentissa - Clínica dental en Lázaro Cárdenas',
            'description' => 'Odontología integral en Lázaro Cárdenas, Michoacán: limpieza, ortodoncia, implantes, blanqueamiento y odontopediatría. Agenda tu cita por WhatsApp.',
            'structured_data' => true,
        ],
        '/acerca-de-nosotros' => [
            'title' => 'Acerca de nosotros - Dentissa',
            'description' => 'Conoce al equipo de Dentissa, su trayectoria y los valores con los que cuidamos la salud dental de cada paciente.',
            'structured_data' => false,
        ],
        '/galeria' => [
            'title' => 'Galería - Dentissa',
            'description' => 'Recorre las instalaciones de Dentissa y mira el antes y después de nuestros tratamientos de blanqueamiento, ortodoncia e implantes.',
            'structured_data' => false,
        ],
        '/contacto' => [
            'title' => 'Contacto y ubicación - Dentissa',
            'description' => 'Escríbenos por WhatsApp, consulta nuestro horario y encuentra cómo llegar a la clínica Dentissa en Lázaro Cárdenas.',
            'structured_data' => true,
        ],
    ],

];
