<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;
use Illuminate\Support\Str;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * GET /api/v1/portal/appointments/{id}/prescriptions (spec 025, CA5, CA6, CA8, CA12, CA20): the
 * recommendations and prescriptions of a completed appointment of the patient. Anything else of
 * the patient answers an empty 200; someone else's appointment answers like a missing one.
 */

function portalPrescriptionsUrl(string $id): string
{
    return '/api/v1/portal/appointments/'.$id.'/prescriptions';
}

/**
 * @param  list<string>  $medications
 */
function portalFollowUp(AppointmentModel $appointment, ?string $recommendations, array $medications): AppointmentTrackingModel
{
    $tracking = AppointmentTrackingModel::create([
        'id' => (string) Str::uuid(),
        'appointment_id' => $appointment->id,
        'reason' => 'Dolor de muela',
        'symptoms' => ['Dolor'],
        'diagnosis' => 'Caries',
        'procedure_performed' => 'Resina',
        'observations' => 'Sin novedad',
        'recommendations' => $recommendations,
    ]);

    foreach ($medications as $medication) {
        AppointmentTrackingPrescriptionModel::create([
            'id' => (string) Str::uuid(),
            'appointment_tracking_id' => $tracking->id,
            'medication' => $medication,
            'dosage' => '500 mg',
            'duration_days' => 7,
            'daily_frequency' => 3,
            'instructions' => 'Tomar con alimentos',
        ]);
    }

    return $tracking;
}

it('gives the recommendations and each prescription of a completed appointment', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'completada']);
    portalFollowUp($appointment, 'Cepillarse tres veces al día', ['Amoxicilina', 'Ibuprofeno']);

    $response = $this->getJson(portalPrescriptionsUrl($appointment->id))->assertOk();

    expect($response->json('data.recommendations'))->toBe('Cepillarse tres veces al día')
        ->and(collect($response->json('data.prescriptions'))->pluck('medication')->sort()->values()->all())->toBe(['Amoxicilina', 'Ibuprofeno'])
        ->and(array_keys($response->json('data.prescriptions.0')))->toBe(['medication', 'dosage', 'daily_frequency', 'duration_days', 'instructions'])
        ->and($response->json('data.prescriptions.0.dosage'))->toBe('500 mg')
        ->and($response->json('data.prescriptions.0.daily_frequency'))->toBe(3)
        ->and($response->json('data.prescriptions.0.duration_days'))->toBe(7)
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('answers an empty 200 for a completed appointment without follow-up', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'completada']);

    $this->getJson(portalPrescriptionsUrl($appointment->id))
        ->assertOk()
        ->assertExactJson(['data' => ['recommendations' => null, 'prescriptions' => []]]);
});

it('answers null recommendations when the follow-up has none', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'completada']);
    portalFollowUp($appointment, null, ['Amoxicilina']);

    $response = $this->getJson(portalPrescriptionsUrl($appointment->id))->assertOk();

    expect($response->json('data.recommendations'))->toBeNull()
        ->and($response->json('data.prescriptions'))->toHaveCount(1);
});

it('keeps the recommendations of a follow-up without prescriptions (A19)', function () {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => 'completada']);
    portalFollowUp($appointment, 'Cepillarse tres veces al día', []);

    $this->getJson(portalPrescriptionsUrl($appointment->id))
        ->assertOk()
        ->assertExactJson(['data' => ['recommendations' => 'Cepillarse tres veces al día', 'prescriptions' => []]]);
});

it('answers an empty 200 for an open or cancelled appointment of the patient', function (string $status) {
    $patient = $this->actingAsPatient();
    $appointment = $this->createAppointment(['patient_id' => $patient->id, 'status' => $status]);

    $this->getJson(portalPrescriptionsUrl($appointment->id))
        ->assertOk()
        ->assertExactJson(['data' => ['recommendations' => null, 'prescriptions' => []]]);
})->with(['asignada', 'cancelada']);

it('answers an appointment of another patient exactly like a missing one, even with follow-up (abuse)', function () {
    $this->actingAsPatient();
    $foreign = $this->createAppointment(['patient_id' => $this->createPatient()->id, 'status' => 'completada']);
    portalFollowUp($foreign, 'Cepillarse tres veces al día', ['Amoxicilina']);

    $missing = $this->getJson(portalPrescriptionsUrl('0f1e2d3c-4b5a-4968-8778-695a4b3c2d1e'))->assertNotFound();
    $response = $this->getJson(portalPrescriptionsUrl($foreign->id))->assertNotFound();

    expect($response->getContent())->toBe($missing->getContent())
        ->and($response->json())->toBe(['error' => 'Appointment not found.']);
});

it('answers 400 to an id that is not a UUID', function () {
    $this->actingAsPatient();

    $this->getJson(portalPrescriptionsUrl('no-es-uuid'))
        ->assertStatus(400)
        ->assertExactJson(['error' => 'Invalid appointment id.']);
});
