<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * The attendance microservice reads appointments with the X-Service-Key header, without a user:
 * one appointment when an ESP32 scans a QR, and the whole clinic day.
 */

beforeEach(function () {
    config()->set('services.attendance.key', 'test-service-key');
});

// --- Happy path ---

it('returns an appointment with the service key', function () {
    $appointment = $this->createAppointment(['date' => '2026-11-04', 'time' => '10:30', 'status' => 'reprogramada']);

    $response = $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalAppointmentUrl($appointment->id));

    $response->assertOk()
        ->assertJsonPath('data.id', $appointment->id)
        ->assertJsonPath('data.date', '2026-11-04')
        ->assertJsonPath('data.status', 'reprogramada')
        ->assertJsonPath('data.patient_id', $appointment->patient_id)
        ->assertJsonStructure(['data' => ['time', 'patient_name', 'user_name', 'treatment_name']]);
});

it('returns every appointment of the clinic day, for every doctor', function () {
    $this->travelTo(Carbon::parse('2026-11-04 23:30:00', 'America/Mexico_City'));
    $doctor = $this->createUserWithRole('Doctor');
    $mine = $this->createAppointment(['date' => '2026-11-04', 'time' => '09:00']);
    $theirs = $this->createAppointment(['date' => '2026-11-04', 'time' => '23:45', 'user_id' => $doctor->id]);
    $tomorrow = $this->createAppointment(['date' => '2026-11-05', 'time' => '09:00']);

    $response = $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalTodayAppointmentsUrl());

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toContain($mine->id, $theirs->id)
        ->not->toContain($tomorrow->id);
});

// --- Not found and invalid ids ---

it('answers 404 for an appointment that does not exist', function () {
    $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalAppointmentUrl((string) Str::uuid()))
        ->assertNotFound()
        ->assertJsonStructure(['error']);
});

it('answers 400 for an id that is not a uuid', function () {
    $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalAppointmentUrl('not-a-uuid'))
        ->assertBadRequest()
        ->assertExactJson(['error' => 'Invalid appointment id.']);
});

// --- Service key ---

it('answers 401 without the service key', function (string $url) {
    $this->getJson($url)
        ->assertUnauthorized()
        ->assertExactJson(['error' => 'Invalid service key.']);
})->with([
    'one appointment' => fn () => '/api/v1/internal/appointments/'.Str::uuid(),
    'today' => fn () => '/api/v1/internal/appointments/today',
]);

it('answers 401 with a wrong service key', function () {
    $appointment = $this->createAppointment();

    $this->withHeader('X-Service-Key', 'wrong-key')
        ->getJson($this->internalAppointmentUrl($appointment->id))
        ->assertUnauthorized()
        ->assertJsonMissingPath('data');
});

it('refuses every request when no service key is configured', function (?string $configured) {
    config()->set('services.attendance.key', $configured);

    $this->withHeader('X-Service-Key', '')
        ->getJson($this->internalTodayAppointmentsUrl())
        ->assertUnauthorized();
})->with([
    'null' => [null],
    'empty' => [''],
]);

it('does not accept a staff session in place of the service key', function (string $role) {
    $this->actingAs($this->createUserWithRole($role), 'sanctum');
    $appointment = $this->createAppointment();

    $this->getJson($this->internalAppointmentUrl($appointment->id))->assertUnauthorized();
    $this->getJson($this->internalTodayAppointmentsUrl())->assertUnauthorized();
})->with(['Administrador', 'Doctor', 'Asistente']);

it('does not accept a patient session in place of the service key', function () {
    $this->actingAsPatient();

    $this->getJson($this->internalTodayAppointmentsUrl())->assertUnauthorized();
});

// --- Rate limit ---

it('does not cap the QR scans of the service at the anonymous limit of 10 a minute', function () {
    $appointment = $this->createAppointment();

    for ($scan = 1; $scan <= 30; $scan++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.30'])
            ->withHeader('X-Service-Key', 'test-service-key')
            ->getJson($this->internalAppointmentUrl($appointment->id))
            ->assertOk();
    }
});

it('keeps a wrong service key at 10 attempts a minute per IP', function () {
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.31'])
            ->withHeader('X-Service-Key', 'wrong-key')
            ->getJson($this->internalTodayAppointmentsUrl())
            ->assertUnauthorized();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.31'])
        ->withHeader('X-Service-Key', 'wrong-key')
        ->getJson($this->internalTodayAppointmentsUrl())
        ->assertStatus(429);
});

it('still serves the service while a wrong key is being throttled from the same IP', function () {
    for ($attempt = 1; $attempt <= 11; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.32'])
            ->withHeader('X-Service-Key', 'wrong-key')
            ->getJson($this->internalTodayAppointmentsUrl());
    }

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.32'])
        ->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalTodayAppointmentsUrl())
        ->assertOk();
});

// --- Unexpected failure ---

it('answers a generic 500 when reading an appointment fails unexpectedly', function () {
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RuntimeException('detalle interno'));

    $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalAppointmentUrl((string) Str::uuid()))
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when listing the clinic day fails unexpectedly', function () {
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findAllByStatusAndDateOrPatientId')->andThrow(new RuntimeException('detalle interno'));

    $this->withHeader('X-Service-Key', 'test-service-key')
        ->getJson($this->internalTodayAppointmentsUrl())
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});
