<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 025 (CA11, CA15, CA16, P5, TM1, TM6): every route of the portal refuses whoever is not an
 * active patient. Nobody signed in gets 401; staff of any role, active or not, and a patient
 * whose account the clinic deactivated get 403, without any data.
 */

/**
 * @return list<string>
 */
function portalAccessRoutes(): array
{
    return [
        'GET /portal/appointments',
        'GET /portal/appointments/next',
        'GET /portal/appointments/{id}/qr',
        'GET /portal/appointments/{id}/prescriptions',
    ];
}

/**
 * @return array<string, array{0: string}>
 */
function portalAccessRouteDataset(): array
{
    return collect(portalAccessRoutes())->mapWithKeys(fn (string $route): array => [$route => [$route]])->all();
}

function portalAccessRequest(object $test, string $route): TestResponse
{
    [$method, $path] = explode(' ', $route, 2);
    $path = '/api/v1'.str_replace('{id}', '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34', $path);

    return $test->json($method, $path);
}

it('answers 401 to someone who is not signed in', function (string $route) {
    portalAccessRequest($this, $route)
        ->assertUnauthorized()
        ->assertJsonMissingPath('data');
})->with(portalAccessRouteDataset());

it('answers 403 to staff of any role, active or not', function (string $route, string $role, string $status) {
    $this->actingAs($this->createUserWithRole($role, ['status' => $status]), 'sanctum');

    portalAccessRequest($this, $route)
        ->assertForbidden()
        ->assertJsonMissingPath('data');
})->with(portalAccessRouteDataset())->with([
    'administrador' => ['administrador', 'active'],
    'asistente' => ['asistente', 'active'],
    'doctor' => ['doctor', 'active'],
    'personal inactivo' => ['administrador', 'inactive'],
]);

it('answers 403 to a patient whose account the clinic deactivated', function (string $route) {
    $this->actingAsPatient(['status' => 'inactive']);

    portalAccessRequest($this, $route)
        ->assertForbidden()
        ->assertExactJson(['error' => 'Your account is inactive.']);
})->with(portalAccessRouteDataset());
