<?php

declare(strict_types=1);

use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

/**
 * Spec 014, CA4 and CA15: the side menu and the home panel only offer the screens and the
 * actions each actor can actually use; the server already refuses the rest. Spec 018 opens
 * the agenda to the assistant and to the doctor.
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

it('offers the assistant the agenda and the records but not patient registration (spec 018, CA1)', function () {
    $this->actingAsNonAdminUser('Asistente');

    $this->get('/dashboard')->assertOk()
        ->assertSee('href="/agenda"', false)
        ->assertSee('href="/expedientes-clinicos"', false)
        ->assertDontSee('data-create-patient-open', false);
});

it('offers the doctor the agenda and the records (spec 018, CA5)', function () {
    $this->actingAsNonAdminUser('Doctor');

    $this->get('/dashboard')->assertOk()
        ->assertSee('href="/agenda"', false)
        ->assertSee('href="/expedientes-clinicos"', false)
        ->assertDontSee('data-create-patient-open', false);
});

it('offers the patient neither the agenda nor the records', function () {
    $this->actingAsPatient();

    $this->get('/dashboard')->assertOk()
        ->assertDontSee('href="/agenda"', false)
        ->assertDontSee('href="/expedientes-clinicos"', false);
});

it('greets the doctor with a neutral message, not the patient one (spec 014, R4 and R36)', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');

    // Spec 020 (c5): the start of the staff greets by name with the date, without a description.
    $this->get('/dashboard')->assertOk()
        ->assertSee('¡Hola, '.$doctor->first_name.'!')
        ->assertDontSee('de tus pacientes')
        ->assertDontSee('Tu salud dental');
});

it('offers the administrator the monthly reports (spec 019, CA1)', function () {
    $this->actingAsAdmin();

    $this->get('/dashboard')->assertOk()->assertSee('href="/reportes"', false);
});

it('offers the reports to nobody but the administrator (spec 019, CA10)', function (string $actor) {
    $actor === 'Paciente' ? $this->actingAsPatient() : $this->actingAsNonAdminUser($actor);

    $this->get('/dashboard')->assertOk()->assertDontSee('href="/reportes"', false);
})->with(['Asistente', 'Doctor', 'Paciente']);
