<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA19 and CA20 (TM2, TM3, TM8): an address that does not exist answers the
 * "No encontrada" page - in Spanish, with the way back, without echoing anything of the
 * request and without opening a session for a visitor - and a fixed Spanish body when JSON is
 * asked for.
 */

beforeEach(function () {
    config(['app.debug' => false]);
});

it('shows the "No encontrada" page to a visitor, with the way to the public site', function (string $path) {
    $response = $this->get($path);

    $response->assertNotFound();
    $response->assertViewIs('errors::404');
    $response->assertSee('No encontramos esta página');
    $response->assertSee('href="'.route('inicio').'"', false);
    $response->assertDontSee('href="'.route('dashboard').'"', false);
    $response->assertSee('lang="es"', false);
})->with([
    'unknown address' => ['/pagina-que-no-existe'],
    'under a protected screen' => ['/usuarios/no-existe'],
    'nested' => ['/a/b/c/d'],
])->skip('016: pendiente de T071');

it('shows the way to the panel to someone signed in', function (string $actor) {
    $actor === 'paciente' ? $this->actingAsPatient() : $this->actingAsNonAdminUser($actor);

    $response = $this->get('/pagina-que-no-existe');

    $response->assertNotFound();
    $response->assertViewIs('errors::404');
    $response->assertSee('href="'.route('dashboard').'"', false);
})->with(['Asistente', 'Doctor', 'paciente'])->skip('016: pendiente de T071');

it('recognises the session from the token cookie alone', function () {
    $token = $this->createUserWithRole('Asistente')->createToken('user-auth')->plainTextToken;

    $response = $this->withUnencryptedCookie('auth_token', $token)->get('/pagina-que-no-existe');

    $response->assertNotFound();
    $response->assertSee('href="'.route('dashboard').'"', false);
})->skip('016: pendiente de T071');

it('never echoes the requested address (abuse)', function (string $path, string $needle) {
    $response = $this->get($path);

    $response->assertNotFound();
    expect($response->getContent())->not->toContain($needle)
        ->and($response->getContent())->not->toContain('could not be found')
        ->and($response->getContent())->not->toContain('NotFoundHttpException');
})->with([
    'markup in the path' => ['/%3Cscript%3Ealert(7731)%3C/script%3E', '7731'],
    'markup in the query' => ['/no-existe?q=%3Cimg%20src%3Dx%20onerror%3Dalert(7731)%3E', '7731'],
    'plain path' => ['/ruta-secreta-7731', 'ruta-secreta-7731'],
]);

it('opens no session for a visitor (abuse)', function () {
    $response = $this->get('/pagina-que-no-existe');

    $response->assertNotFound();
    expect(collect($response->headers->getCookies())->map->getName()->all())
        ->not->toContain(config('session.cookie'))
        ->not->toContain('XSRF-TOKEN');
});

it('answers a fixed Spanish body, without the address, when JSON is asked for (abuse)', function () {
    $response = $this->getJson('/ruta-secreta-7731');

    $response->assertNotFound();
    $response->assertExactJson(['message' => 'No encontramos esta página.']);
})->skip('016: pendiente de T071');
