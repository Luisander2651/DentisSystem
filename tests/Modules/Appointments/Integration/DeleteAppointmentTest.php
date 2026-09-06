<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

it('allows an admin to delete an appointment', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->deleteJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(200)
        ->assertJson(['message' => 'Appointment deleted successfully']);

    $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
});

it('rejects a non-admin staff member with 403', function () {
    $this->actingAsNonAdminUser();
    $appointment = $this->createAppointment();

    $response = $this->deleteJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(403)
        ->assertJson(['error' => 'You are not allowed to perform this action (appointments.delete).']);

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id]);
});

it('rejects an inactive admin with 403', function () {
    $this->actingAsAdmin(['status' => 'inactive']);
    $appointment = $this->createAppointment();

    $response = $this->deleteJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(403)
        ->assertJson(['error' => 'Your account is inactive.']);
});

it('rejects an authenticated patient with 403', function () {
    $this->actingAsPatient();
    $appointment = $this->createAppointment();

    $response = $this->deleteJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(403)
        ->assertJson(['error' => 'Authentication is required.']);
});

it('rejects unauthenticated request with 401', function () {
    $appointment = $this->createAppointment();

    $response = $this->deleteJson($this->appointmentUrl($appointment->id));

    $response->assertStatus(401);
});
