<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA14, CA15, CA34 and CA36 (A66): what the HTML of every screen must carry, for
 * each role that can open it - one main title, the brand with its alternative text, the tab
 * icons pointing to files of our own, the versioned access image, every field with its
 * label, and the purpose of the fields of the access screens.
 *
 * One case per check and screen. A case waits for the task that migrates that screen:
 * PAGE_STRUCTURE_PENDING names it, and the task takes its entry out (A61).
 */

const PAGE_STRUCTURE_SCREENS = [
    'inicio' => [null, '/'],
    'acerca' => [null, '/acerca-de-nosotros'],
    'galeria' => [null, '/galeria'],
    'contacto' => [null, '/contacto'],
    'login' => [null, '/login'],
    'register' => [null, '/register'],
    'forgot-password' => [null, '/forgot-password'],
    'reset-password' => [null, '/reset-password?token=abc&email=a%40example.com'],
    'logout' => ['Asistente', '/logout'],
    'dashboard-administrador' => ['Administrador', '/dashboard'],
    'dashboard-asistente' => ['Asistente', '/dashboard'],
    'dashboard-doctor' => ['Doctor', '/dashboard'],
    'dashboard-paciente' => ['paciente', '/dashboard'],
    'agenda' => ['Administrador', '/agenda'],
    'pacientes' => ['Administrador', '/pacientes'],
    'usuarios' => ['Administrador', '/usuarios'],
    'tratamientos' => ['Administrador', '/tratamientos'],
    'contenido' => ['Administrador', '/contenido'],
    'expedientes-administrador' => ['Administrador', '/expedientes-clinicos'],
    'expedientes-asistente' => ['Asistente', '/expedientes-clinicos'],
    'expedientes-doctor' => ['Doctor', '/expedientes-clinicos'],
    'expediente' => ['Administrador', '/expedientes-clinicos/{patient}'],
];

const PAGE_STRUCTURE_ACCESS_SCREENS = ['login', 'register', 'forgot-password', 'reset-password'];

/** check => screen => task that unblocks the case. */
const PAGE_STRUCTURE_PENDING = [
    'h1' => [
        'dashboard-administrador' => 'T041',
        'dashboard-asistente' => 'T041',
        'dashboard-doctor' => 'T041',
        'dashboard-paciente' => 'T041',
        'agenda' => 'T064',
        'pacientes' => 'T042',
        'usuarios' => 'T045',
        'tratamientos' => 'T047',
        'contenido' => 'T061',
        'expedientes-administrador' => 'T048',
        'expedientes-asistente' => 'T048',
        'expedientes-doctor' => 'T048',
        'expediente' => 'T048',
    ],
    'brand' => [
    ],
    'icons' => [
    ],
    'access-image' => [
    ],
    'labels' => [
        'dashboard-administrador' => 'T084',
        'agenda' => 'T068',
        'pacientes' => 'T044',
        'usuarios' => 'T084',
        'tratamientos' => 'T047',
        'expedientes-administrador' => 'T050',
        'expedientes-asistente' => 'T050',
        'expedientes-doctor' => 'T050',
        'expediente' => 'T050',
    ],
    'autocomplete' => [
    ],
];

/**
 * @return array<string, array{string}>
 */
function pageStructureCases(?array $only = null): array
{
    $cases = [];

    foreach (array_keys(PAGE_STRUCTURE_SCREENS) as $screen) {
        if ($only === null || in_array($screen, $only, true)) {
            $cases[$screen] = [$screen];
        }
    }

    return $cases;
}

function openStructureScreen(CoreIntegrationTestCase $test, string $check, string $screen): DOMXPath
{
    $pending = PAGE_STRUCTURE_PENDING[$check][$screen] ?? null;

    if ($pending !== null) {
        $test->markTestSkipped('016: pendiente de '.$pending);
    }

    [$role, $path] = PAGE_STRUCTURE_SCREENS[$screen];

    $html = (function () use ($role, $path): string {
        match ($role) {
            null => null,
            'paciente' => $this->actingAsPatient(),
            'Administrador' => $this->actingAsAdmin(),
            default => $this->actingAsNonAdminUser($role),
        };

        $response = $this->get(str_replace('{patient}', $this->createPatient()->id, $path));
        $response->assertOk();

        return $response->getContent();
    })->call($test);

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return new DOMXPath($document);
}

/**
 * The file under public/ a same-site address points to, or null when it points elsewhere.
 */
function publicFileOf(string $address): ?string
{
    $path = parse_url($address, PHP_URL_PATH);
    $host = parse_url($address, PHP_URL_HOST);

    if (! is_string($path) || ($host !== null && $host !== parse_url((string) config('app.url'), PHP_URL_HOST))) {
        return null;
    }

    return public_path(ltrim($path, '/'));
}

it('has exactly one main title', function (string $screen) {
    $page = openStructureScreen($this, 'h1', $screen);

    expect($page->query('//h1')->length)->toBe(1);
})->with(pageStructureCases());

it('shows the brand with its alternative text', function (string $screen) {
    $page = openStructureScreen($this, 'brand', $screen);
    // The access photo lives next to the brand files but is a decorative picture.
    $images = $page->query('//img[contains(@src, "/images/brand/") and not(contains(@src, "/images/brand/access"))]');

    expect($images->length)->toBeGreaterThan(0);

    foreach ($images as $image) {
        expect(trim($image->getAttribute('alt')))->not->toBe('')
            ->and(is_file((string) publicFileOf($image->getAttribute('src'))))->toBeTrue();
    }
})->with(pageStructureCases());

it('points its tab icons to files of our own that exist', function (string $screen) {
    $page = openStructureScreen($this, 'icons', $screen);

    foreach (['icon', 'apple-touch-icon'] as $rel) {
        $links = $page->query('//link[@rel="'.$rel.'"]');

        expect($links->length)->toBeGreaterThan(0);

        foreach ($links as $link) {
            $file = publicFileOf($link->getAttribute('href'));

            expect($file)->not->toBeNull()
                ->and(is_file((string) $file))->toBeTrue()
                ->and(filesize((string) $file))->toBeGreaterThan(0);
        }
    }
})->with(pageStructureCases());

it('shows the versioned access image', function (string $screen) {
    $page = openStructureScreen($this, 'access-image', $screen);
    $images = $page->query('//img[contains(@src, "/images/brand/access")]');

    expect($images->length)->toBe(1)
        ->and(filesize((string) publicFileOf($images->item(0)->getAttribute('src'))))->toBeGreaterThan(0);
})->with(pageStructureCases(PAGE_STRUCTURE_ACCESS_SCREENS));

it('gives every field its label', function (string $screen) {
    $page = openStructureScreen($this, 'labels', $screen);
    $fields = $page->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button")] | //select | //textarea');
    $unlabelled = [];

    foreach ($fields as $field) {
        $id = $field->getAttribute('id');
        $labelled = ($id !== '' && $page->query('//label[@for="'.$id.'"]')->length > 0)
            || $page->query('ancestor::label', $field)->length > 0
            || trim($field->getAttribute('aria-label')) !== ''
            || ($field->getAttribute('aria-labelledby') !== '' && $page->query('//*[@id="'.$field->getAttribute('aria-labelledby').'"]')->length > 0);

        if (! $labelled) {
            $unlabelled[] = $field->nodeName.'#'.$id.'[name='.$field->getAttribute('name').']';
        }
    }

    expect($unlabelled)->toBe([]);
})->with(pageStructureCases());

it('declares the purpose of the fields of the access screens', function (string $screen, array $expected) {
    $page = openStructureScreen($this, 'autocomplete', $screen);
    $declared = [];

    foreach ($page->query('//input[not(@type="hidden") and not(@type="checkbox")]') as $field) {
        $declared[$field->getAttribute('name')] = $field->getAttribute('autocomplete');
    }

    expect($declared)->toBe($expected);
})->with([
    'login' => ['login', ['email' => 'email', 'password' => 'current-password']],
    'register' => ['register', [
        'first_name' => 'given-name',
        'last_name' => 'family-name',
        'email' => 'email',
        'password' => 'new-password',
        'confirm_password' => 'new-password',
    ]],
]);
