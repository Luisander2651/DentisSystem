<?php

declare(strict_types=1);

use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Finding 20 (Unit 4, option C). The staff name shown on an appointment used to be built by
 * CONCATENATING first_name and last_name and then re-splitting the result with
 * UserName::fromString(). Those are already two columns, so the round trip bought nothing
 * and cost two defects, both covered here.
 */
it('shows the doctor with the same name as the users screen', function () {
    $admin = $this->actingAsAdmin();
    $patient = $this->createPatient();

    // Stored with the per-field capitalisation data created before the fix still carries.
    $doctor = $this->createUserWithRole('Doctor', [
        'first_name' => 'Maria de jesus',
        'last_name' => 'Rebolledo murga',
    ]);

    $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id]);

    $onAppointment = $this->getJson("/api/v1/appointments/patient/{$patient->id}")
        ->assertOk()
        ->json('data.0.user_name');

    $onUsersScreen = collect($this->getJson('/api/v1/users')->assertOk()->json('data'))
        ->firstWhere('id', $doctor->id);

    expect($onAppointment)->toBe('Maria De Jesus Rebolledo Murga')
        ->and($onAppointment)->toBe($onUsersScreen['first_name'].' '.$onUsersScreen['last_name']);
});

it('keeps listing appointments when a doctor has an empty surname', function () {
    // Defect 1c. UserName::create('Ana', '') is accepted, but fromString('Ana ') threw, and
    // the repository maps the whole collection, so ONE such doctor took the entire listing
    // down with a 500. New records can no longer be created this way (BR-12 requires
    // last_name), but rows created before it are still readable data.
    $this->actingAsAdmin();
    $patient = $this->createPatient();

    $doctor = $this->createUserWithRole('Doctor', ['first_name' => 'Ana', 'last_name' => '']);
    $this->createAppointment(['patient_id' => $patient->id, 'user_id' => $doctor->id]);
    $this->createAppointment(['patient_id' => $patient->id]);

    $this->getJson("/api/v1/appointments/patient/{$patient->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
