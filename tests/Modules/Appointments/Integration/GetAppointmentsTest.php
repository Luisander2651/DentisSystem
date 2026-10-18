<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

// -----------------------------------------------------------------
// GetAppointmentById / GetAppointmentsByPatientId - abierto a cualquier staff
// -----------------------------------------------------------------

it('any authenticated staff can get an appointment by id', function () {
    $this->actingAsNonAdminUser('Asistente');
    $appointment = $this->createAppointment();

    $response = $this->getJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $appointment->id);
});

it('returns 404 when the appointment does not exist', function () {
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->getJson($this->appointmentUrl((string) Str::uuid()));

    $response->assertStatus(404);
});

it('any authenticated staff can list appointments by patient id', function () {
    $this->actingAsNonAdminUser('Asistente');
    $patient = $this->createPatient();
    $this->createAppointment(['patient_id' => $patient->id]);
    $this->createAppointment(['patient_id' => $patient->id]);

    $response = $this->getJson("/api/v1/appointments/patient/{$patient->id}");

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

// -----------------------------------------------------------------
// GetAllApointmentsByStatusAndDate - administrador y asistente (spec 018); el doctor, sus citas
// -----------------------------------------------------------------

it('admin can list appointments by status and date', function () {
    $this->actingAsAdmin();
    $this->createAppointment(['date' => '2026-09-01']);

    $response = $this->getJson($this->appointmentsUrl().'?date=2026-09-01');

    $response->assertStatus(200);
});

it('lets the assistant list appointments too (spec 018, CA1)', function () {
    $this->actingAsNonAdminUser('Asistente');
    $this->createAppointment(['date' => '2026-09-01']);

    $response = $this->getJson($this->appointmentsUrl());

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

// -----------------------------------------------------------------
// GetTodayAppointments - todo el staff (spec 018); el doctor recibe solo sus citas
// -----------------------------------------------------------------

it('admin can get today appointments', function () {
    $this->actingAsAdmin();

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertStatus(200);
});

it('gives today appointments to the assistant too (spec 018, replaces spec 014 CA10)', function () {
    $this->actingAsNonAdminUser('Asistente');

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertOk();
});

// -----------------------------------------------------------------
// Catalogo agenda/treatments - administrador y asistente, para agendar (spec 018, D11)
// -----------------------------------------------------------------

it('gives the agenda treatments catalog to the assistant, who books appointments (spec 018, CA2)', function () {
    $this->actingAsNonAdminUser('Asistente');
    $this->createTreatment(['name' => 'Limpieza dental']);

    $response = $this->getJson($this->agendaTreatmentsUrl());

    $response->assertOk();
    $response->assertJsonPath('data.0.name', 'Limpieza dental');
});

it('refuses the agenda treatments catalog to the doctor, who does not book appointments (spec 018)', function () {
    $this->actingAsNonAdminUser('Doctor');
    $this->createTreatment(['name' => 'Limpieza dental']);

    $response = $this->getJson($this->agendaTreatmentsUrl());

    $response->assertForbidden();
});

it('rejects unauthenticated request on the treatments catalog', function () {
    $response = $this->getJson($this->agendaTreatmentsUrl());

    $response->assertStatus(401);
});
