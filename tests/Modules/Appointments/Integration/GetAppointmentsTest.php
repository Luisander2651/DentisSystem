<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

// -----------------------------------------------------------------
// GetAppointmentById / GetAppointmentsByPatientId - abierto a cualquier staff
// -----------------------------------------------------------------

it('any authenticated staff can get an appointment by id', function () {
    $this->actingAsNonAdminUser();
    $appointment = $this->createAppointment();

    $response = $this->getJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $appointment->id);
});

it('returns 404 when the appointment does not exist', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->appointmentUrl((string) Str::uuid()));

    $response->assertStatus(404);
});

it('any authenticated staff can list appointments by patient id', function () {
    $this->actingAsNonAdminUser();
    $patient = $this->createPatient();
    $this->createAppointment(['patient_id' => $patient->id]);
    $this->createAppointment(['patient_id' => $patient->id]);

    $response = $this->getJson("/api/v1/appointments/patient/{$patient->id}");

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

// -----------------------------------------------------------------
// GetAllApointmentsByStatusAndDate - admin-only (403 explicito)
// -----------------------------------------------------------------

it('admin can list appointments by status and date', function () {
    $this->actingAsAdmin();
    $this->createAppointment(['date' => '2026-09-01']);

    $response = $this->getJson($this->appointmentsUrl().'?date=2026-09-01');

    $response->assertStatus(200);
});

it('non-admin staff gets 403 when listing appointments', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->appointmentsUrl());

    $response->assertStatus(403);
});

// -----------------------------------------------------------------
// GetTodayAppointments - admin-only, PERO sin try/catch (BR-5b: 500, no 403)
// -----------------------------------------------------------------

it('admin can get today appointments', function () {
    $this->actingAsAdmin();

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertStatus(200);
});

it('non-admin staff gets 500 (not 403) on today appointments due to missing try/catch (documented behavior, BR-5b)', function () {
    $this->actingAsNonAdminUser();

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertStatus(500);
});

// -----------------------------------------------------------------
// Catalogo agenda/treatments - abierto a cualquier staff (corregido, ver BR-5)
// -----------------------------------------------------------------

it('any authenticated staff can view the treatments catalog for booking', function () {
    $this->actingAsNonAdminUser();
    $this->createTreatment(['name' => 'Limpieza dental']);

    $response = $this->getJson($this->agendaTreatmentsUrl());

    $response->assertStatus(200);
    $response->assertJsonCount(1, 'data');
});

it('rejects unauthenticated request on the treatments catalog', function () {
    $response = $this->getJson($this->agendaTreatmentsUrl());

    $response->assertStatus(401);
});
