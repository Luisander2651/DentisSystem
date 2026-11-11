<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 020 (CA9, CA10, CA11, CA16, A16, A17, A33): the HTML of /dashboard by role, following
 * "Qué ve cada rol" of the plan. The data comes later from the API; here only what the server
 * renders: the header, the sections that exist or not, the actions each role is offered and the
 * pieces that go away. The inactive staff member gets "Sin permiso"; the patient gets the start of
 * spec 025 (c3).
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
});

/**
 * @return array{0: DOMXPath, 1: string}
 */
function dashboardScreen(CoreIntegrationTestCase $test, string $role): array
{
    $html = (function () use ($role): string {
        $user = match ($role) {
            'paciente' => $this->actingAsPatient(['first_name' => 'Ángela', 'email' => 'angela.inicio@example.test']),
            default => tap($this->createUserWithRole($role, ['first_name' => 'Alma', 'email' => 'alma.inicio@example.test']), fn ($user) => $this->actingAs($user, 'sanctum')),
        };

        return $this->get('/dashboard')->assertOk()->getContent();
    })->call($test);

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return [new DOMXPath($document), $html];
}

function dashboardText(DOMNode $node): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $node->textContent));
}

/**
 * The section or navigation whose heading reads exactly $heading, or null.
 */
function dashboardRegion(DOMXPath $page, string $heading): ?DOMNode
{
    foreach ($page->query('//main//*[self::h2 or self::h3]') as $title) {
        if (dashboardText($title) === $heading || str_starts_with(dashboardText($title), $heading.' ')) {
            $region = $title;

            while ($region !== null && ! in_array($region->nodeName, ['section', 'nav'], true)) {
                $region = $region->parentNode;
            }

            return $region;
        }
    }

    return null;
}

/**
 * @return list<string>
 */
function dashboardControls(DOMXPath $page, ?DOMNode $region): array
{
    if ($region === null) {
        return [];
    }

    return array_map(dashboardText(...), iterator_to_array($page->query('.//a | .//button', $region)));
}

it('titles the tab "Inicio - Dentissa" for every role, with one main title', function (string $role) {
    [$page] = dashboardScreen($this, $role);

    expect(dashboardText($page->query('//title')->item(0)))->toBe('Inicio - Dentissa')
        ->and($page->query('//h1')->length)->toBe(1);
})->with(['Administrador', 'Asistente', 'Doctor', 'paciente']);

it('greets the staff member by name with the date of the clinic', function (string $role) {
    [$page] = dashboardScreen($this, $role);
    $header = $page->query('//h1')->item(0);

    expect(dashboardText($header))->toBe('¡Hola, Alma!')
        ->and(dashboardText($header->parentNode->parentNode))->toContain('Jueves 22 de octubre');
})->with(['Administrador', 'Asistente', 'Doctor']);

it('offers "Nueva cita" in the header only to the administrator', function (string $role, bool $inHeader) {
    [$page] = dashboardScreen($this, $role);
    $header = $page->query('//h1')->item(0)->parentNode->parentNode;

    expect(in_array('Nueva cita', dashboardControls($page, $header), true))->toBe($inHeader);
})->with([
    'administrador' => ['Administrador', true],
    'asistente' => ['Asistente', false],
    'doctor' => ['Doctor', false],
]);

it('offers on the next appointment the actions of the role', function (string $role, array $actions) {
    [$page] = dashboardScreen($this, $role);
    $controls = dashboardControls($page, dashboardRegion($page, 'Siguiente cita'));

    expect(array_values(array_intersect(['Completar', 'Ver detalle'], $controls)))->toBe($actions);
})->with([
    'administrador' => ['Administrador', ['Completar', 'Ver detalle']],
    'asistente' => ['Asistente', ['Ver detalle']],
    'doctor' => ['Doctor', ['Completar', 'Ver detalle']],
]);

it('offers the quick actions of the role', function (string $role, array $actions) {
    [$page] = dashboardScreen($this, $role);

    expect(dashboardControls($page, dashboardRegion($page, 'Acciones rápidas')))->toBe($actions);
})->with([
    'administrador' => ['Administrador', ['Nuevo paciente', 'Buscar paciente', 'Agenda de hoy', 'Registrar seguimiento']],
    'asistente' => ['Asistente', ['Nueva cita', 'Buscar paciente', 'Agenda de hoy']],
    'doctor' => ['Doctor', ['Buscar paciente', 'Agenda de hoy', 'Registrar seguimiento']],
]);

it('has the sections of the role and none of the others', function (string $role, array $present, array $absent) {
    [$page, $html] = dashboardScreen($this, $role);

    foreach ($present as $heading) {
        expect(dashboardRegion($page, $heading))->not->toBeNull("Falta la sección \"{$heading}\".");
    }

    foreach ($absent as $heading) {
        expect(dashboardRegion($page, $heading))->toBeNull("Sobra la sección \"{$heading}\".");
    }

    if (in_array('Completadas sin seguimiento', $absent, true)) {
        expect($html)->not->toContain('Registrar seguimiento');
    }
})->with([
    'administrador' => ['Administrador', ['Siguiente cita', 'Cómo va el día', 'Agenda de hoy', 'Octubre', 'Citas sin cerrar', 'Completadas sin seguimiento'], []],
    'asistente' => ['Asistente', ['Siguiente cita', 'Cómo va el día', 'Agenda de hoy', 'Citas sin cerrar'], ['Octubre', 'Completadas sin seguimiento']],
    'doctor' => ['Doctor', ['Siguiente cita', 'Cómo va el día', 'Agenda de hoy', 'Citas sin cerrar', 'Completadas sin seguimiento'], ['Octubre']],
]);

it('leaves out the pieces of the old start and never shows a counter as "-" (CA11)', function (string $role) {
    [$page, $html] = dashboardScreen($this, $role);
    $texts = array_map(dashboardText(...), iterator_to_array($page->query('//main//*[not(*)]')));

    expect($html)->not->toContain('Estado')
        ->and(mb_strtolower($html))->not->toContain('actividad reciente')
        ->and($html)->not->toContain('Nuevo usuario')
        ->and($html)->not->toContain('data-create-user-open')
        ->and($html)->not->toContain('alma.inicio@example.test')
        ->and($texts)->not->toContain('-')
        ->and($texts)->not->toContain('–');
})->with(['Administrador', 'Asistente', 'Doctor']);

it('hands over the month in course to the administrator only', function (string $role, bool $handsOver) {
    [, $html] = dashboardScreen($this, $role);

    expect(str_contains($html, 'data-current-month="2026-10"'))->toBe($handsOver);
})->with([
    'administrador' => ['Administrador', true],
    'asistente' => ['Asistente', false],
    'doctor' => ['Doctor', false],
]);

/*
 * Spec 025 (c3, CA1, CA2): the start of the patient takes the greeting of the staff, with the
 * date and without the hour, and leaves the initial (A16), the e-mail and the role of the old one.
 */
it('greets the patient by name with the date, without the initial, the e-mail or the hour', function () {
    [$page, $html] = dashboardScreen($this, 'paciente');
    $header = $page->query('//h1')->item(0);
    $texts = array_map(dashboardText(...), iterator_to_array($page->query('//main//*[not(*)]')));

    expect(dashboardText($header))->toBe('¡Hola, Ángela!')
        ->and(dashboardText($header->parentNode->parentNode))->toBe('¡Hola, Ángela! Jueves 22 de octubre')
        ->and($texts)->not->toContain('Á')
        ->and($html)->not->toContain('angela.inicio@example.test');
});

it('gives the patient the sections of c3 and none of the staff', function () {
    [$page] = dashboardScreen($this, 'paciente');

    foreach (['Próxima cita', 'Mis citas', '¿Necesitas cambiar tu cita?'] as $heading) {
        expect(dashboardRegion($page, $heading))->not->toBeNull("Falta la sección \"{$heading}\".");
    }

    foreach (['Siguiente cita', 'Cómo va el día', 'Agenda de hoy', 'Acciones rápidas', 'Citas sin cerrar', 'Completadas sin seguimiento'] as $heading) {
        expect(dashboardRegion($page, $heading))->toBeNull("Sobra la sección \"{$heading}\".");
    }
});

it('offers the patient the actions of c3 and the ways to the clinic and to "Mis citas"', function () {
    [$page] = dashboardScreen($this, 'paciente');
    $hrefs = fn (string $heading): array => array_map(
        fn (DOMElement $link): string => (string) parse_url($link->getAttribute('href'), PHP_URL_PATH),
        iterator_to_array($page->query('.//a', dashboardRegion($page, $heading))),
    );

    expect(dashboardControls($page, dashboardRegion($page, 'Próxima cita')))->toBe(['Contacta a la clínica', 'Reintentar', 'Descargar QR', 'Ver detalle'])
        ->and($hrefs('Próxima cita'))->toBe(['/contacto'])
        ->and($hrefs('Mis citas'))->toBe(['/mis-citas'])
        ->and($hrefs('¿Necesitas cambiar tu cita?'))->toBe(['/contacto']);
});

it('refuses the start to an inactive staff member with the "Sin permiso" page (A17)', function () {
    $this->actingAsInactiveAdmin();

    $this->get('/dashboard')->assertForbidden()->assertViewIs('errors::403');
});

it('sends a visitor without a session to the login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
