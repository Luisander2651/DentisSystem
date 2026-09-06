<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use Illuminate\Support\Str;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

// -----------------------------------------------------------------
// A. Happy path
// -----------------------------------------------------------------

it('completes appointment without prescriptions', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Appointment completed successfully',
            'data' => [
                'appointment_tracking' => [
                    'appointment_id' => $appointment->id,
                    'observations' => null,
                    'recommendations' => null,
                ],
                'prescriptions' => [],
            ],
        ]);
});

it('completes appointment with one prescription', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload()],
        ]),
    );

    $response->assertStatus(201);
    $response->assertJsonCount(1, 'data.prescriptions');
    $response->assertJsonPath('data.prescriptions.0.medication', 'Ibuprofeno');
    $response->assertJsonPath('data.prescriptions.0.duration_days', 7);
    $response->assertJsonPath('data.prescriptions.0.daily_frequency', 3);
});

it('completes appointment with multiple prescriptions', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [
                $this->validPrescriptionPayload(['medication' => 'Ibuprofeno']),
                $this->validPrescriptionPayload(['medication' => 'Amoxicilina']),
                $this->validPrescriptionPayload(['medication' => 'Paracetamol']),
            ],
        ]),
    );

    $response->assertStatus(201);
    $response->assertJsonCount(3, 'data.prescriptions');
});

it('completes appointment with observations and recommendations', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'observations' => 'Paciente sensible al frío',
            'recommendations' => 'Evitar bebidas frías por 48 horas',
        ]),
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.appointment_tracking.observations', 'Paciente sensible al frío')
        ->assertJsonPath('data.appointment_tracking.recommendations', 'Evitar bebidas frías por 48 horas');
});

it('completes appointment omitting observations and recommendations keys', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $payload = $this->validCompletePayload();
    unset($payload['observations'], $payload['recommendations']);

    $response = $this->postJson($this->completeAppointmentUrl($appointment->id), $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.appointment_tracking.observations', null)
        ->assertJsonPath('data.appointment_tracking.recommendations', null);
});

it('completes appointment with blank observations normalizes to null', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['observations' => '   ']),
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.appointment_tracking.observations', null);
});

// -----------------------------------------------------------------
// B. Autenticación / autorización (respuestas del middleware OnlyAdmin)
// -----------------------------------------------------------------

it('rejects unauthenticated request', function () {
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('rejects authenticated non user actor', function () {
    $this->actingAsPatient();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only users can access this resource.']);
});

it('rejects inactive admin', function () {
    $this->actingAsInactiveAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(403)
        ->assertJson(['error' => 'Your account is inactive.']);
});

it('rejects authenticated non admin role', function () {
    $this->actingAsNonAdminUser();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(403)
        ->assertJson(['error' => 'Only administrators can access this resource.']);
});

it('allows admin role case insensitively', function () {
    $this->createRole('ADMINISTRADOR');
    $this->actingAsAdmin(['role_id' => $this->createRole('ADMINISTRADOR')->id]);
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(201);
});

// -----------------------------------------------------------------
// C. Cita no encontrada / id inválido
// -----------------------------------------------------------------

it('returns 409 when appointment not found', function () {
    $this->actingAsAdmin();
    $missingId = (string) Str::uuid();

    $response = $this->postJson(
        $this->completeAppointmentUrl($missingId),
        $this->validCompletePayload(),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => "Appointment with ID {$missingId} not found."]);
});

it('returns 400 when appointment id is not a uuid', function () {
    $this->actingAsAdmin();

    $response = $this->postJson(
        $this->completeAppointmentUrl('not-a-uuid'),
        $this->validCompletePayload(),
    );

    $response->assertStatus(400);
});

// -----------------------------------------------------------------
// D. Estado de la cita
// -----------------------------------------------------------------

it('returns 409 when appointment is cancelled', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => 'cancelada']);

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => 'Invalid status value. Allowed values are: completada, cancelada.']);
});

it('allows completing a rescheduled appointment', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => 'reprogramada']);

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(201);
});

// -----------------------------------------------------------------
// E. Tracking duplicado
// -----------------------------------------------------------------

it('returns 409 when tracking already exists for appointment', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $this->postJson($this->completeAppointmentUrl($appointment->id), $this->validCompletePayload())
        ->assertStatus(201);

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => "An appointment tracking record already exists for appointment {$appointment->id}."]);
});

// -----------------------------------------------------------------
// F. Shape de prescriptions[]
// -----------------------------------------------------------------

it('returns 409 when prescription item is not an array', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['prescriptions' => ['not-an-array']]),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => 'Prescription at index 0 must be an array.']);
});

it('returns 409 when prescription has unexpected fields', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['foo' => 'bar'])],
        ]),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => 'Prescription at index 0 has unexpected fields: foo.']);
});

it('returns 409 when prescription uses snake case keys instead of camel case', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [[
                'medication' => 'Ibuprofeno',
                'dosage' => '400mg',
                'duration_days' => 7,
                'daily_frequency' => 3,
            ]],
        ]),
    );

    // El chequeo de "unexpected fields" corre antes que el de "missing fields",
    // así que este payload en snake_case falla por campos inesperados, no por faltantes.
    $response->assertStatus(409)
        ->assertJson(['error' => 'Prescription at index 0 has unexpected fields: duration_days, daily_frequency.']);
});

it('returns 409 when prescription is missing required fields', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [['medication' => 'Ibuprofeno']],
        ]),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => 'Prescription at index 0 is missing required fields: dosage, durationDays, dailyFrequency.']);
});

it('returns 409 reporting correct index for second invalid prescription', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [
                $this->validPrescriptionPayload(),
                ['medication' => 'Amoxicilina'],
            ],
        ]),
    );

    $response->assertStatus(409)
        ->assertJson(['error' => 'Prescription at index 1 is missing required fields: dosage, durationDays, dailyFrequency.']);
});

// -----------------------------------------------------------------
// G. Value Objects (400)
// -----------------------------------------------------------------

it('returns 400 when reason is empty', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['reason' => '']),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Reason must be a non-empty string.']);
});

it('returns 400 when diagnosis is empty', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['diagnosis' => '']),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Diagnosis must be a non-empty string.']);
});

it('returns 400 when procedure performed is empty', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['procedure_performed' => '']),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Procedure performed must be a non-empty string.']);
});

it('returns 400 when symptoms contains empty string', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['symptoms' => ['dolor', '']]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'All symptoms values must be non-empty strings.']);
});

it('returns 400 when symptoms contains non string', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload(['symptoms' => ['dolor', 123]]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'All symptoms values must be non-empty strings.']);
});

it('returns 400 when prescription medication is empty', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['medication' => ''])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Medication must be a non-empty string.']);
});

it('returns 400 when prescription dosage is empty', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['dosage' => ''])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Dosage must be a non-empty string.']);
});

it('returns 400 when duration days is zero', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['durationDays' => 0])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Duration days must be an integer between 1 and 365.']);
});

it('returns 400 when duration days exceeds 365', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['durationDays' => 366])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Duration days must be an integer between 1 and 365.']);
});

it('returns 400 when daily frequency is zero', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['dailyFrequency' => 0])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Daily frequency must be an integer between 1 and 24.']);
});

it('returns 400 when daily frequency exceeds 24', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['dailyFrequency' => 25])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Daily frequency must be an integer between 1 and 24.']);
});

it('truncates non integer duration days instead of rejecting type', function () {
    // Comportamiento documentado: el use case castea con (int) antes de construir el VO,
    // así que "5.9" se trunca silenciosamente a 5 en vez de fallar por tipo inválido.
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['durationDays' => '5.9'])],
        ]),
    );

    $response->assertStatus(201)
        ->assertJsonPath('data.prescriptions.0.duration_days', 5);
});

it('rejects non numeric string duration days via range error not type error', function () {
    // "abc" se castea a (int) 0, que falla la validación de RANGO (no una de tipo).
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload(['durationDays' => 'abc'])],
        ]),
    );

    $response->assertStatus(400)
        ->assertJson(['error' => 'Duration days must be an integer between 1 and 365.']);
});

// -----------------------------------------------------------------
// H. Persistencia real
// -----------------------------------------------------------------

it('persists tracking and updates appointment status in database', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload()],
        ]),
    );

    $response->assertStatus(201);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'completada',
    ]);

    $this->assertDatabaseHas('appointment_tracking', [
        'appointment_id' => $appointment->id,
        'reason' => 'Dolor persistente',
        'diagnosis' => 'Caries profunda',
    ]);

    $this->assertDatabaseCount('appointment_tracking_prescriptions', 1);

    $this->assertDatabaseHas('appointment_tracking_prescriptions', [
        'medication' => 'Ibuprofeno',
        'duration_days' => 7,
        'daily_frequency' => 3,
    ]);
});

it('persisted prescription references correct tracking id', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->postJson(
        $this->completeAppointmentUrl($appointment->id),
        $this->validCompletePayload([
            'prescriptions' => [$this->validPrescriptionPayload()],
        ]),
    );

    $response->assertStatus(201);

    $trackingId = $response->json('data.appointment_tracking.id');

    $this->assertNotNull($trackingId);
    $this->assertSame(
        $trackingId,
        AppointmentTrackingModel::findOrFail($trackingId)->prescriptions()->first()->appointment_tracking_id,
    );
    $this->assertSame($trackingId, $response->json('data.prescriptions.0.appointment_tracking_id'));
});
