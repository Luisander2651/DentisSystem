<?php

declare(strict_types=1);

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, CA20 and CA41 (TM2): the other two rejections a person can meet on a screen - a
 * method the address does not admit and a form that expired - also answer their own page in
 * Spanish with the way back, and a fixed Spanish body when JSON is asked for (A49, A58).
 *
 * No screen of the application can produce a 419 on its own (every form posts to the API), so
 * the test registers a form route that fails the way an expired form does.
 */

beforeEach(function () {
    config(['app.debug' => false]);

    Route::middleware('web')->post('/_test/formulario', function () {
        throw new TokenMismatchException('CSRF token mismatch.');
    });
});

it('shows its own page when a screen does not admit the method', function (bool $signedIn) {
    if ($signedIn) {
        $this->actingAsNonAdminUser();
    }

    $response = $this->post('/login');

    $response->assertMethodNotAllowed();
    $response->assertViewIs('errors::405');
    $response->assertSee('Esta acción no está disponible');
    $response->assertSee('href="'.route($signedIn ? 'dashboard' : 'inicio').'"', false);
    $response->assertSee('lang="es"', false);
    expect($response->getContent())->not->toContain('method is not supported')
        ->and($response->getContent())->not->toContain('Method Not Allowed');
})->with(['visitor' => [false], 'signed in' => [true]])->skip('016: pendiente de T088');

it('shows its own page when a form expired', function (bool $signedIn) {
    if ($signedIn) {
        $this->actingAsNonAdminUser();
    }

    $response = $this->post('/_test/formulario');

    $response->assertStatus(419);
    $response->assertViewIs('errors::419');
    $response->assertSee('La página caducó');
    $response->assertSee('href="'.route($signedIn ? 'dashboard' : 'inicio').'"', false);
    $response->assertSee('lang="es"', false);
    expect($response->getContent())->not->toContain('CSRF')
        ->and($response->getContent())->not->toContain('Page Expired');
})->with(['visitor' => [false], 'signed in' => [true]])->skip('016: pendiente de T088');

it('answers a fixed Spanish body when JSON is asked for', function (string $path, int $status, string $message) {
    $response = $this->postJson($path);

    $response->assertStatus($status);
    $response->assertExactJson(['message' => $message]);
})->with([
    'method not allowed' => ['/login', 405, 'Esta acción no está disponible.'],
    'expired form' => ['/_test/formulario', 419, 'La página caducó. Vuelve a cargarla e inténtalo de nuevo.'],
])->skip('016: pendiente de T071');
