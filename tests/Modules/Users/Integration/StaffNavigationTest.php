<?php

declare(strict_types=1);

use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

/**
 * Spec 014, CA4 and CA15: the side menu and the home panel only offer the screens and the
 * actions each actor can actually use; the server already refuses the rest.
 */
beforeEach(function () {
    $this->withoutVite();
});

it('offers the administrator the agenda, the records and patient registration', function () {
    $this->actingAsAdmin();

    $this->get('/dashboard')->assertOk()
        ->assertSee('href="/agenda"', false)
        ->assertSee('href="/expedientes-clinicos"', false)
        ->assertSee('data-create-patient-open', false);
});

it('offers the assistant the records but neither the agenda nor patient registration', function () {
    $this->actingAsNonAdminUser('Asistente');

    $this->get('/dashboard')->assertOk()
        ->assertSee('href="/expedientes-clinicos"', false)
        ->assertDontSee('href="/agenda"', false)
        ->assertDontSee('data-create-patient-open', false);
});

it('offers the doctor the records but not the agenda', function () {
    $this->actingAsNonAdminUser('Doctor');

    $this->get('/dashboard')->assertOk()
        ->assertSee('href="/expedientes-clinicos"', false)
        ->assertDontSee('href="/agenda"', false)
        ->assertDontSee('data-create-patient-open', false);
});

it('offers the patient neither the agenda nor the records', function () {
    $this->actingAsPatient();

    $this->get('/dashboard')->assertOk()
        ->assertDontSee('href="/agenda"', false)
        ->assertDontSee('href="/expedientes-clinicos"', false);
});

it('greets the doctor with their own message, not the patient one (spec 014, R4)', function () {
    $this->actingAsNonAdminUser('Doctor');

    $this->get('/dashboard')->assertOk()
        ->assertSee('Consulta los expedientes clínicos de tus pacientes.')
        ->assertDontSee('Tu salud dental');
});
