<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * Spec 018: the doctor completes their own appointments with the clinical follow-up (CA12) and
 * reads the follow-up of their own completed ones (CA9), but never touches another doctor's
 * (CA10, CA13) nor edits a follow-up (CA10). The assistant never completes nor reads it (CA4).
 */

function trackingFor(AppointmentModel $appointment): AppointmentTrackingModel
{
    $tracking = AppointmentTrackingModel::query()->create([
        'id' => (string) Str::uuid(),
        'reason' => 'Revisión de una muela',
        'symptoms' => ['sensibilidad'],
        'diagnosis' => 'Caries superficial',
        'procedure_performed' => 'Resina',
        'observations' => 'Sin complicaciones',
        'recommendations' => 'Usar hilo dental',
        'appointment_id' => $appointment->id,
    ]);

    AppointmentTrackingPrescriptionModel::query()->create([
        'id' => (string) Str::uuid(),
        'medication' => 'Ibuprofeno',
        'dosage' => '400mg',
        'duration_days' => 3,
        'daily_frequency' => 2,
        'instructions' => 'Tomar con alimentos',
        'appointment_tracking_id' => $tracking->id,
    ]);

    return $tracking;
}

function trackingUrl(string $appointmentId): string
{
    return "/api/v1/appointments/{$appointmentId}/tracking";
}

/**
 * @return array<string, mixed>
 */
function clinicalSnapshot(): array
{
    return collect(['appointments', 'appointment_tracking', 'appointment_tracking_prescriptions'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->toArray()])
        ->all();
}

it('lets the doctor complete their own appointment with its follow-up and prescriptions', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['user_id' => $doctor->id]);

    $response = $this->postJson($this->completeAppointmentUrl($appointment->id), $this->validCompletePayload([
        'prescriptions' => [$this->validPrescriptionPayload()],
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.appointment_tracking.appointment_id', $appointment->id)
        ->assertJsonCount(1, 'data.prescriptions');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
    $this->assertDatabaseHas('appointment_tracking', ['appointment_id' => $appointment->id, 'diagnosis' => 'Caries profunda']);
})->skip('018: pendiente de T027');

it('answers 409, as for the administrator, when the doctor completes an appointment that does not exist', function () {
    $this->actingAsNonAdminUser('Doctor');

    $this->postJson($this->completeAppointmentUrl((string) Str::uuid()), $this->validCompletePayload())
        ->assertStatus(409);
})->skip('018: pendiente de T027');

it('lets the doctor read the follow-up of their own completed appointment', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['user_id' => $doctor->id, 'status' => 'completada']);
    trackingFor($appointment);

    $this->getJson(trackingUrl($appointment->id))
        ->assertOk()
        ->assertJsonPath('data.appointment_tracking.diagnosis', 'Caries superficial')
        ->assertJsonPath('data.prescriptions.0.medication', 'Ibuprofeno');
})->skip('018: pendiente de T027');

it('answers 404 when the doctor reads their own completed appointment without follow-up', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['user_id' => $doctor->id, 'status' => 'completada']);

    $this->getJson(trackingUrl($appointment->id))
        ->assertNotFound()
        ->assertExactJson(['error' => 'Appointment tracking not found.']);
})->skip('018: pendiente de T027');

it('refuses the doctor the follow-up of another doctor without giving away any clinical data', function () {
    $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['user_id' => $this->createUserWithRole('doctor')->id, 'status' => 'completada']);
    trackingFor($appointment);

    $this->getJson(trackingUrl($appointment->id))
        ->assertForbidden()
        ->assertExactJson(['error' => 'You are not allowed to perform this action (appointment-tracking.view-own).']);
})->skip('018: pendiente de T027');

it('refuses the doctor completing an appointment of another doctor and leaves it untouched', function () {
    $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['user_id' => $this->createUserWithRole('doctor')->id]);
    $before = clinicalSnapshot();

    $this->postJson($this->completeAppointmentUrl($appointment->id), $this->validCompletePayload())
        ->assertForbidden()
        ->assertExactJson(['error' => 'You are not allowed to perform this action (appointment-tracking.create-own).']);

    expect(clinicalSnapshot())->toEqual($before);
})->skip('018: pendiente de T027');

it('refuses the doctor and the assistant editing a follow-up or its prescriptions, even on the doctor\'s own appointment', function (string $role, string $method, string $route) {
    $doctor = $this->createUserWithRole('doctor');
    $appointment = $this->createAppointment(['user_id' => $doctor->id, 'status' => 'completada']);
    $tracking = trackingFor($appointment);
    $prescriptionId = AppointmentTrackingPrescriptionModel::query()->where('appointment_tracking_id', $tracking->id)->value('id');
    $role === 'Doctor' ? $this->actingAs($doctor, 'sanctum') : $this->actingAsNonAdminUser('Asistente');
    $before = clinicalSnapshot();

    $url = str_replace(['{tracking}', '{prescription}'], [$tracking->id, $prescriptionId], $route);
    $payload = $method === 'deleteJson' ? [] : $this->validPrescriptionPayload(['diagnosis' => 'Otro diagnóstico']);

    $this->{$method}($url, $payload)->assertForbidden();

    expect(clinicalSnapshot())->toEqual($before);
})->with(['Doctor', 'Asistente'])->with([
    'edit the follow-up' => ['putJson', '/api/v1/appointment-tracking/{tracking}'],
    'add a prescription' => ['postJson', '/api/v1/appointment-tracking/{tracking}/prescriptions'],
    'edit a prescription' => ['putJson', '/api/v1/appointment-tracking/prescriptions/{prescription}'],
    'delete a prescription' => ['deleteJson', '/api/v1/appointment-tracking/prescriptions/{prescription}'],
]);

it('refuses the assistant completing an appointment or reading its follow-up', function (string $method, string $permission) {
    $appointment = $this->createAppointment(['status' => $method === 'getJson' ? 'completada' : 'asignada']);
    if ($method === 'getJson') {
        trackingFor($appointment);
    }
    $this->actingAsNonAdminUser('Asistente');
    $before = clinicalSnapshot();

    $url = $method === 'getJson' ? trackingUrl($appointment->id) : $this->completeAppointmentUrl($appointment->id);
    $this->{$method}($url, $method === 'getJson' ? [] : $this->validCompletePayload())
        ->assertForbidden()
        ->assertExactJson(['error' => "You are not allowed to perform this action ({$permission})."]);

    expect(clinicalSnapshot())->toEqual($before);
})->with([
    'complete' => ['postJson', 'appointment-tracking.create'],
    'read the follow-up' => ['getJson', 'appointment-tracking.view'],
])->skip('018: pendiente de T027');

it('refuses the follow-up to a patient, to inactive staff and to a guest', function (string $actor, int $status, array $body) {
    $appointment = $this->createAppointment(['status' => 'completada']);
    trackingFor($appointment);
    match ($actor) {
        'patient' => $this->actingAsPatient(),
        'inactive' => $this->actingAsInactiveAdmin(),
        'guest' => null,
    };

    $this->getJson(trackingUrl($appointment->id))
        ->assertStatus($status)
        ->assertExactJson($body);
})->with([
    'patient' => ['patient', 403, ['error' => 'Only staff can access this resource.']],
    'inactive staff' => ['inactive', 403, ['error' => 'Your account is inactive.']],
    'guest' => ['guest', 401, ['message' => 'Unauthenticated.']],
])->skip('018: pendiente de T027');
