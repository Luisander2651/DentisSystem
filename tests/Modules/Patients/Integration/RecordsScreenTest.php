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

/**
 * The header row of one records table, located by the id of its body. The appointments
 * history keeps its own "Acciones" column, so only these three tables are checked.
 */
function recordsTableHeader(string $html, string $bodyId): string
{
    preg_match('#<thead[^>]*>((?:(?!</thead>).)*)</thead>\s*<tbody id="'.$bodyId.'"#s', $html, $match);

    return $match[1] ?? '';
}

$editableTables = ['record-contact-info-body', 'record-address-body', 'record-medical-data-body'];

it('tells the doctor the records are read-only, without an actions column (spec 014, R5 and R6)', function () use ($editableTables) {
    $this->actingAsNonAdminUser('Doctor');

    $html = $this->get('/expedientes-clinicos')->assertOk()
        ->assertDontSee('gestionar su historia clinica')
        ->assertDontSee('ver y editar')
        ->getContent();

    foreach ($editableTables as $bodyId) {
        expect(recordsTableHeader($html, $bodyId))->not->toBe('')->not->toContain('Acciones');
    }
});

it('keeps the actions column for staff who may edit the records', function (string $role) use ($editableTables) {
    $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    $html = $this->get('/expedientes-clinicos')->assertOk()->getContent();

    foreach ($editableTables as $bodyId) {
        expect(recordsTableHeader($html, $bodyId))->toContain('Acciones');
    }
})->with(['Administrador', 'Asistente']);
