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
