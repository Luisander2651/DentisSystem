<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 025 (CA10, CA11, CA15, CA16, RNF): the screens of the patient portal. Only an active
 * patient opens them; a deactivated patient and any staff member get "Sin permiso", and someone
 * without a session goes to sign in. They ask search engines not to index them (A14: there is
 * no sitemap yet, so noindex is what keeps them out).
 */

it('opens "Mis citas" to an active patient, with its header and noindex', function () {
    $this->actingAsPatient();

    $response = $this->get('/mis-citas');

    $response->assertOk();
    $response->assertSee('<meta name="robots" content="noindex">', false);
    $response->assertSee('Mis citas');
    $response->assertSee('data-page-badge>Mi cuenta<', false);
    $response->assertSee('Consulta tus citas, de la más reciente a la más antigua.');
});

it('shows "Sin permiso" on "Mis citas" to a deactivated patient and to staff (abuse)', function (string $actor) {
    match ($actor) {
        'paciente inactivo' => $this->actingAsPatient(['status' => 'inactive']),
        'administrador' => $this->actingAsAdmin(),
        default => $this->actingAsNonAdminUser($actor),
    };

    $response = $this->get('/mis-citas');

    $response->assertForbidden();
    $response->assertSee('No tienes permiso para ver esta pantalla');
})->with(['paciente inactivo', 'administrador', 'Asistente', 'Doctor']);

it('sends someone without a session to sign in', function () {
    $this->get('/mis-citas')->assertRedirect('/login');
});

it('keeps noindex out of the screens of the staff', function () {
    $this->actingAsAdmin();

    $this->get('/agenda')->assertOk()->assertDontSee('<meta name="robots" content="noindex">', false);
});

/*
 * Spec 025 (CA10, CA15): the patient lands on their start and the menu offers only their screens
 * and signing out; a deactivated patient with a session still open gets "Sin permiso".
 */

const PORTAL_STAFF_SCREENS = ['/agenda', '/tratamientos', '/pacientes', '/expedientes-clinicos', '/contenido', '/usuarios', '/reportes'];

it('offers the patient only "Inicio", "Mis citas" and signing out', function (string $path) {
    $this->actingAsPatient();

    $response = $this->get($path)->assertOk();

    $response->assertSee('href="/dashboard"', false);
    $response->assertSee('href="/mis-citas"', false);
    $response->assertSee('Cerrar sesión');

    foreach (PORTAL_STAFF_SCREENS as $screen) {
        $response->assertDontSee('href="'.$screen.'"', false);
    }
})->with(['/dashboard', '/mis-citas']);

it('asks search engines not to index the start of the patient', function () {
    $this->actingAsPatient();

    $this->get('/dashboard')->assertOk()->assertSee('<meta name="robots" content="noindex">', false);
});

it('shows "Sin permiso" on the start to a deactivated patient (abuse)', function () {
    $this->actingAsPatient(['status' => 'inactive']);

    $this->get('/dashboard')
        ->assertForbidden()
        ->assertSee('No tienes permiso para ver esta pantalla');
});

it('leaves "Mis citas" out of the menu of the staff', function (string $role) {
    $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    $this->get('/dashboard')->assertOk()->assertDontSee('href="/mis-citas"', false);
})->with(['Administrador', 'Asistente', 'Doctor']);
