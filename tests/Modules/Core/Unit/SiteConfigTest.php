<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 026, CA7, CA11 and CA17 (A6, A29, A37, A43 of /analyze 026): the public site reads the
 * clinic's data from config/site.php. Every contact value is the real one when its SITE_*
 * variable says so and, until then, a coherent provisional value that the configuration marks
 * as provisional (amendment of CA7 asked by the user, 2026-11-12). No placeholder text is left.
 */

const SITE_CONTACT_VARIABLES = [
    'whatsapp' => 'SITE_WHATSAPP',
    'phone' => 'SITE_PHONE',
    'email' => 'SITE_EMAIL',
    'address' => 'SITE_ADDRESS',
    'maps_url' => 'SITE_MAPS_URL',
    'instagram' => 'SITE_INSTAGRAM',
    'facebook' => 'SITE_FACEBOOK',
    'schedule' => 'SITE_SCHEDULE',
];

/**
 * Loads config/site.php again with the given environment, restoring it afterwards.
 *
 * @param  array<string, string|null>  $environment  null removes the variable
 * @return array<string, mixed>
 */
function siteConfigWith(array $environment): array
{
    $saved = [];

    foreach ($environment as $name => $value) {
        $saved[$name] = [getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null];

        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        } else {
            putenv("{$name}={$value}");
            $_ENV[$name] = $_SERVER[$name] = $value;
        }
    }

    try {
        return require base_path('config/site.php');
    } finally {
        foreach ($saved as $name => [$process, $env, $server]) {
            $process === false ? putenv($name) : putenv("{$name}={$process}");

            if ($env === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $env;
            }

            if ($server === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $server;
            }
        }
    }
}

/**
 * @return array<string, mixed>
 */
function siteConfigWithoutContactVariables(): array
{
    return siteConfigWith(array_fill_keys(array_values(SITE_CONTACT_VARIABLES), null));
}

function siteDescriptionFits(string $description): bool
{
    $length = mb_strlen($description);

    return $length >= 70 && $length <= 160;
}

it('gives every contact value a provisional default marked as provisional', function (string $key) {
    $site = siteConfigWithoutContactVariables();

    expect($site['contact'][$key])->not->toBeEmpty()
        ->and($site['provisional'][$key])->toBeTrue();
})->with(array_keys(SITE_CONTACT_VARIABLES));

it('falls back to the provisional value when the variable is empty', function (string $key, string $variable) {
    $site = siteConfigWith([$variable => '   ']);
    $provisional = siteConfigWithoutContactVariables()['contact'][$key];

    expect($site['contact'][$key])->toBe($provisional)
        ->and($site['provisional'][$key])->toBeTrue();
})->with(fn (): array => array_map(fn (string $key): array => [$key, SITE_CONTACT_VARIABLES[$key]], array_keys(SITE_CONTACT_VARIABLES)));

it('uses the real value and drops the provisional mark when the variable is set', function () {
    $site = siteConfigWith([
        'SITE_WHATSAPP' => '5215512345678',
        'SITE_PHONE' => '+52 55 9876 5432',
        'SITE_EMAIL' => 'citas@clinica.mx',
        'SITE_ADDRESS' => 'Calle Real 10, Coyoacán, CDMX',
        'SITE_MAPS_URL' => 'https://maps.example.com/clinica',
        'SITE_INSTAGRAM' => 'https://www.instagram.com/clinica.real',
        'SITE_FACEBOOK' => 'https://www.facebook.com/clinica.real',
        'SITE_SCHEDULE' => '1-5 08:00-18:00; 6 09:00-13:00',
    ]);

    expect($site['contact']['whatsapp'])->toBe('5215512345678')
        ->and($site['contact']['email'])->toBe('citas@clinica.mx')
        ->and($site['contact']['schedule'])->toBe([
            1 => ['08:00', '18:00'], 2 => ['08:00', '18:00'], 3 => ['08:00', '18:00'],
            4 => ['08:00', '18:00'], 5 => ['08:00', '18:00'], 6 => ['09:00', '13:00'],
        ])
        ->and(array_filter($site['provisional']))->toBe([]);
});

it('keeps the provisional schedule of the proposal: weekdays 9 to 19, Saturday 9 to 14, Sunday closed', function () {
    expect(siteConfigWithoutContactVariables()['contact']['schedule'])->toBe([
        1 => ['09:00', '19:00'], 2 => ['09:00', '19:00'], 3 => ['09:00', '19:00'],
        4 => ['09:00', '19:00'], 5 => ['09:00', '19:00'], 6 => ['09:00', '14:00'],
    ]);
});

it('leaves no placeholder text nor a social network without an account', function () {
    $values = array_map('strval', array_filter(siteConfigWithoutContactVariables()['contact'], 'is_string'));

    foreach ($values as $value) {
        expect($value)->not->toContain('[Pendiente')
            ->and($value)->not->toContain('[CORREO');
    }

    expect(rtrim($values['facebook'], '/'))->not->toBeIn(['https://facebook.com', 'https://www.facebook.com'])
        ->and(rtrim($values['instagram'], '/'))->not->toBeIn(['https://instagram.com', 'https://www.instagram.com']);
});

it('lists the five treatments Dentissa offers, each with its summary', function () {
    $treatments = config('site.treatments');

    expect(array_column($treatments, 'name'))->toBe([
        'Limpieza y Prevención',
        'Ortodoncia',
        'Implantes Dentales',
        'Blanqueamiento Dental',
        'Odontopediatría',
    ]);

    foreach ($treatments as $treatment) {
        expect($treatment['summary'])->not->toBeEmpty();
    }
});

it('lists the six indexable routes', function () {
    expect(config('site.indexable'))->toBe([
        '/',
        '/acerca-de-nosotros',
        '/galeria',
        '/contacto',
        '/aviso-de-privacidad',
        '/terminos-y-condiciones',
    ]);
});

it('gives each page of the site a unique title and a description of 70 to 160 characters', function () {
    $pages = config('site.pages');

    expect(array_keys($pages))->toBe(['/', '/acerca-de-nosotros', '/galeria', '/contacto'])
        ->and(array_unique(array_column($pages, 'title')))->toHaveCount(4)
        ->and(array_unique(array_column($pages, 'description')))->toHaveCount(4);

    foreach ($pages as $path => $page) {
        expect(siteDescriptionFits($page['description']))->toBeTrue("{$path}: ".mb_strlen($page['description']).' caracteres');
    }
});

it('measures the description limits at their boundaries (controls)', function (int $length, bool $fits) {
    expect(siteDescriptionFits(str_repeat('á', $length)))->toBe($fits);
})->with([
    '69 is too short' => [69, false],
    '70 fits' => [70, true],
    '160 fits' => [160, true],
    '161 is too long' => [161, false],
]);

it('uses the canonical site URL of the SEO rules', function () {
    $seo = Yaml::parseFile(base_path('.ai/project.yaml'))['seo'];

    expect(config('site.url'))->toBe($seo['site_url']);
});

it('turns the new landing on by default and lets SITE_NEW_LANDING turn it off', function () {
    expect(siteConfigWith(['SITE_NEW_LANDING' => null])['new_landing'])->toBeTrue()
        ->and(siteConfigWith(['SITE_NEW_LANDING' => 'false'])['new_landing'])->toBeFalse();
});

it('declares the variables of the site in .env.example', function (string $variable) {
    expect((string) file_get_contents(base_path('.env.example')))->toMatch('/^'.$variable.'=/m');
})->with(['SITE_NEW_LANDING', ...array_values(SITE_CONTACT_VARIABLES)]);

it('places the provisional clinic in Lázaro Cárdenas, Michoacán, with its map (amendment of CA7, 2026-11-15)', function () {
    $site = siteConfigWithoutContactVariables();
    $pages = collect($site['pages'])->flatMap(fn (array $page): array => [$page['title'], $page['description']]);

    expect($site['contact']['address'])->toBe('Lázaro Cárdenas, Michoacán')
        ->and(urldecode($site['contact']['maps_url']))->toContain('Lázaro Cárdenas Michoacán')
        ->and($site['city'])->toBe('Lázaro Cárdenas')
        ->and($pages->filter(fn (string $text): bool => str_contains($text, 'Ciudad de México'))->all())->toBe([])
        ->and($pages->filter(fn (string $text): bool => str_contains($text, 'Lázaro Cárdenas'))->count())->toBeGreaterThanOrEqual(2);
});
