<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

it('creates an appointment successfully', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertStatus(201)
        ->assertJson(['message' => 'Appointment created successfully']);

    $this->assertDatabaseCount('appointments', 1);
});

it('allows an administrator to book an appointment for any doctor and patient', function () {
    $this->actingAsAdmin();

    $response = $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload());

    $response->assertStatus(201);
});

it('does not log the patient phone or name from the appointments controller (spec 014, OB2.a)', function () {
    // Only the controller's own logs are asserted: the use case and the whatsApp listener that
    // run in the same request still log them (OB2.b, roadmap objective 5; analysis D7).
    $logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $logged->push($entry));
    // The patient has a phone, so the whatsApp listener runs in this request (sync queue):
    // never let it reach the real Twilio API.
    $this->fakeTwilio();
    $this->actingAsAdmin();
    $patient = $this->createPatient(['first_name' => 'Zacarias', 'last_name' => 'Pruebatel']);
    ContactInfoModel::create([
        'patient_id' => $patient->id,
        'phone_number' => '+52 555 010 4321',
        'email' => 'contacto@example.com',
        'emergency_contact' => 'Jane Doe',
    ]);

    $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['patient_id' => $patient->id]))
        ->assertCreated();

    $controllerLogs = $logged
        ->filter(fn (MessageLogged $entry): bool => str_starts_with($entry->message, 'CreateAppointmentController'))
        ->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))
        ->implode("\n");

    expect($controllerLogs)->not->toContain('+52 555 010 4321')
        ->and($controllerLogs)->not->toContain('Zacarias');
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
