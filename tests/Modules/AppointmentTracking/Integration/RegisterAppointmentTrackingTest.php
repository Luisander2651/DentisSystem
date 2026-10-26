<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * Spec 020 (CA8, CA13, CA14, CA15, TM2, TM5): POST /appointments/{id}/tracking registers the
 * follow-up of a completed appointment that has none. The administrator and the doctor of the
 * appointment may; the appointment stays completed and leaves the list without follow-up. Any
 * other state, a second follow-up or a missing appointment is refused with its Spanish text.
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
});

function registerTrackingUrl(string $appointmentId): string
{
    return "/api/v1/appointments/{$appointmentId}/tracking";
}

function registerTrackingExisting(AppointmentModel $appointment): void
{
    AppointmentTrackingModel::query()->create([
        'id' => (string) Str::uuid(),
        'reason' => 'Revisión de una muela',
        'symptoms' => ['sensibilidad'],
        'diagnosis' => 'Caries superficial',
        'procedure_performed' => 'Resina',
        'observations' => null,
        'recommendations' => null,
        'appointment_id' => $appointment->id,
    ]);
}

/**
 * @return array{appointments: int, tracking: int, prescriptions: int}
 */
function registerTrackingCounts(): array
{
    return [
        'appointments' => AppointmentModel::query()->where('status', 'completada')->count(),
        'tracking' => AppointmentTrackingModel::query()->count(),
        'prescriptions' => DB::table('appointment_tracking_prescriptions')->count(),
    ];
}

it('lets the administrator register the follow-up of a completed appointment, with prescriptions', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $response = $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload([
        'prescriptions' => [
            $this->validPrescriptionPayload(),
            array_diff_key($this->validPrescriptionPayload(['medication' => 'Paracetamol']), ['instructions' => true]),
        ],
    ]));

    $response->assertCreated()
        ->assertJsonPath('message', 'Seguimiento registrado.')
        ->assertJsonPath('data.appointment_tracking.appointment_id', $appointment->id)
        ->assertJsonPath('data.appointment_tracking.diagnosis', 'Caries profunda')
        ->assertJsonCount(2, 'data.prescriptions')
        ->assertJsonPath('data.prescriptions.1.instructions', null);

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
    $this->assertDatabaseHas('appointment_tracking', ['appointment_id' => $appointment->id, 'reason' => 'Dolor persistente']);
});

it('lets the doctor register the follow-up of their own completed appointment', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada', 'user_id' => $doctor->id]);

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())
        ->assertCreated()
        ->assertJsonPath('data.appointment_tracking.appointment_id', $appointment->id);

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
});

it('takes the appointment out of the list without follow-up', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->getJson('/api/v1/agenda/without-tracking')->assertOk()->assertJsonPath('data.total', 1);

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())->assertCreated();

    $this->getJson('/api/v1/agenda/without-tracking')
        ->assertOk()
        ->assertExactJson(['data' => ['items' => [], 'total' => 0]]);
});

it('refuses the doctor on an appointment of another doctor without saving anything (abuse)', function () {
    $this->actingAsNonAdminUser('Doctor');
    $otherDoctor = $this->createUserWithRole('doctor');
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada', 'user_id' => $otherDoctor->id]);
    $before = registerTrackingCounts();

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload([
        'prescriptions' => [$this->validPrescriptionPayload()],
    ]))->assertForbidden();

    expect(registerTrackingCounts())->toBe($before);
});

it('refuses the assistant without saving anything (abuse)', function () {
    $this->actingAsNonAdminUser('Asistente');
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);
    $before = registerTrackingCounts();

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())->assertForbidden();

    expect(registerTrackingCounts())->toBe($before);
});

it('refuses an appointment that already has its follow-up (abuse)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);
    registerTrackingExisting($appointment);

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())
        ->assertConflict()
        ->assertExactJson(['error' => 'Esta cita ya tiene seguimiento registrado.']);

    expect(AppointmentTrackingModel::query()->where('appointment_id', $appointment->id)->count())->toBe(1);
});

it('refuses an appointment that is not completed (abuse)', function (string $status) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => $status]);

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())
        ->assertConflict()
        ->assertExactJson(['error' => 'Solo se registra el seguimiento de una cita completada.']);

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => $status]);
    $this->assertDatabaseMissing('appointment_tracking', ['appointment_id' => $appointment->id]);
})->with(['cancelada', 'asignada', 'reprogramada']);

it('answers 404 with its text for an appointment that does not exist', function () {
    $this->actingAsAdmin();

    $this->postJson(registerTrackingUrl((string) Str::uuid()), $this->validCompletePayload())
        ->assertNotFound()
        ->assertExactJson(['error' => 'No se encontró la cita.']);
});

it('answers 409, not 500, when the follow-up is saved in the meantime by a simultaneous submission (TM5)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);
    registerTrackingExisting($appointment);
    $this->missExistingTrackingOnRead();

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload([
        'prescriptions' => [$this->validPrescriptionPayload()],
    ]))
        ->assertConflict()
        ->assertExactJson(['error' => 'Esta cita ya tiene seguimiento registrado.']);

    expect(AppointmentTrackingModel::query()->where('appointment_id', $appointment->id)->count())->toBe(1)
        ->and(DB::table('appointment_tracking_prescriptions')->count())->toBe(0);
});

it('answers the second of two submissions with 409, not 500', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())->assertCreated();

    $this->postJson(registerTrackingUrl($appointment->id), $this->validCompletePayload())
        ->assertConflict()
        ->assertExactJson(['error' => 'Esta cita ya tiene seguimiento registrado.']);

    expect(AppointmentTrackingModel::query()->where('appointment_id', $appointment->id)->count())->toBe(1);
});
