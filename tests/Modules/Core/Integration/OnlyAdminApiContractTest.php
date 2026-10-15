<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 016, TM4: the "Sin permiso" page is for screens. What the API answers to an actor
 * without permission - the 403 and its JSON body, which the page scripts and the future app
 * read - stays exactly as it was.
 */

beforeEach(function () {
    config(['app.debug' => false]);
    $this->withoutRateLimiting();
});

it('keeps the 403 and the JSON of an administrator-only endpoint for each actor without permission', function (string $actor, string $message) {
    match ($actor) {
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'doctor' => $this->actingAsNonAdminUser('Doctor'),
        'paciente' => $this->actingAsPatient(),
        'staff inactivo' => $this->actingAsInactiveAdmin(),
    };

    foreach (['/api/v1/users', '/api/v1/treatments', '/api/v1/gallery-images'] as $endpoint) {
        $response = $this->get($endpoint, ['Accept' => 'text/html']);

        $response->assertForbidden();
        $response->assertExactJson(['error' => $message]);
    }
})->with([
    'asistente' => ['asistente', 'Only administrators can access this resource.'],
    'doctor' => ['doctor', 'Only administrators can access this resource.'],
    'paciente' => ['paciente', 'Only users can access this resource.'],
    'staff inactivo' => ['staff inactivo', 'Your account is inactive.'],
]);

it('keeps the 403 and the JSON of a staff endpoint for a patient and for inactive staff', function (string $actor, string $message) {
    $actor === 'paciente' ? $this->actingAsPatient() : $this->actingAsInactiveAdmin();

    $response = $this->get('/api/v1/agenda/today-appointments', ['Accept' => 'text/html']);

    $response->assertForbidden();
    $response->assertExactJson(['error' => $message]);
})->with([
    'paciente' => ['paciente', 'Only staff can access this resource.'],
    'staff inactivo' => ['staff inactivo', 'Your account is inactive.'],
]);
