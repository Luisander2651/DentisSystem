<?php

declare(strict_types=1);

use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 014, CA4: the records screen is open to every active staff member, read-only for the
 * doctor. Whether the write controls exist is decided in Blade, so it can be asserted here.
 */
beforeEach(function () {
    $this->withoutVite();
});

$writeControls = [
    'data-record-open-contact-form',
    'data-record-open-address-form',
    'data-record-open-medical-form',
    'data-record-delete-button-template',
];

it('shows the records screen read-only to a doctor', function (string $screen) use ($writeControls) {
    $url = $screen === 'list' ? '/expedientes-clinicos' : '/expedientes-clinicos/'.$this->createPatient()->id;
    $this->actingAsNonAdminUser('Doctor');

    $response = $this->get($url)->assertOk()->assertSee('data-records-can-edit="false"', false);

    foreach ($writeControls as $control) {
        $response->assertDontSee($control, false);
    }
})->with(['list', 'patient']);

it('shows the records screen with its write controls to an assistant', function (string $screen) use ($writeControls) {
    $url = $screen === 'list' ? '/expedientes-clinicos' : '/expedientes-clinicos/'.$this->createPatient()->id;
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->get($url)->assertOk()->assertSee('data-records-can-edit="true"', false);

    foreach ($writeControls as $control) {
        $response->assertSee($control, false);
    }
})->with(['list', 'patient']);

it('refuses the records screen to a patient and to an inactive staff member', function (string $actor) {
    match ($actor) {
        'patient' => $this->actingAsPatient(),
        'inactive' => $this->actingAsInactiveAdmin(),
    };

    $this->get('/expedientes-clinicos')->assertForbidden();
})->with(['patient', 'inactive']);

it('sends a visitor without a session to the login page', function () {
    $this->get('/expedientes-clinicos')->assertRedirect('/login');
});
