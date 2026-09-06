<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

it('creates an appointment successfully', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertStatus(201)
        ->assertJson(['message' => 'Appointment created successfully']);

    $this->assertDatabaseCount('appointments', 1);
});

it('allows any authenticated active staff to create an appointment (not just admin)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertStatus(201);
});

it('rejects unauthenticated request', function () {
    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertStatus(401);
});

it('returns 409 when the requested slot overlaps an existing non-cancelled appointment', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '10:00']);

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload([
        'treatment_id' => (string) $treatment->id,
        'date' => '2026-09-01',
        'time' => '10:15',
    ]));

    $response->assertStatus(409);
});

it('does not conflict with a cancelled appointment in the same slot', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment([
        'treatment_id' => $treatment->id,
        'date' => '2026-09-01',
        'time' => '10:00',
        'status' => 'cancelada',
    ]);

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload([
        'treatment_id' => (string) $treatment->id,
        'date' => '2026-09-01',
        'time' => '10:00',
    ]));

    $response->assertStatus(201);
});

it('allows back-to-back appointments with no gap', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-09-01', 'time' => '10:00']);

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload([
        'treatment_id' => (string) $treatment->id,
        'date' => '2026-09-01',
        'time' => '10:30',
    ]));

    $response->assertStatus(201);
});

it('returns 400 when date has an invalid format', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['date' => '01-09-2026']));

    $response->assertStatus(400);
});

it('returns 400 when time has an invalid format', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['time' => '10.00']));

    $response->assertStatus(400);
});

it('returns 500 when the treatment does not exist', function () {
    // TreatmentsService::findById en un id inexistente actualmente no es capturado
    // como AppointmentException/409 por el controller (solo por el generico 500).
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['treatment_id' => '999999']));

    $response->assertStatus(500);
});
