<?php

declare(strict_types=1);

use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingModel;
use App\Modules\AppointmentTracking\Infrastructure\Persistence\Eloquent\Models\AppointmentTrackingPrescriptionModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\AddressesModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\MedicalDataModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

/*
 * Spec 020 (CA7, TM6): the three reads of the dashboard only carry the fields of the contract.
 * With a follow-up, prescriptions and the medical, contact and address data of the patient known,
 * none of those values reaches any of the answers, and no item has a key outside the contract.
 */

/**
 * Values that must never appear in an answer of the dashboard.
 *
 * @return list<string>
 */
function dashboardPrivateValues(): array
{
    return [
        'paula.privada@example.test', 'contacto.privado@example.test', '+52 744 555 0199', 'Contacto de emergencia privado',
        'Alergia privada a la penicilina', 'Medicamento privado diario', 'Calle Privada 123',
        'Motivo privado de la consulta', 'Síntoma privado', 'Diagnóstico privado', 'Procedimiento privado',
        'Observación privada', 'Recomendación privada', 'Receta privada', 'Dosis privada', 'Indicación privada',
    ];
}

/**
 * @return list<string>
 */
function dashboardContractKeys(): array
{
    return ['id', 'date', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'is_past', 'is_next', 'day_label', 'can'];
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->fakeTwilio();

    $patient = $this->createPatient(['first_name' => 'Paula', 'last_name' => 'Prueba', 'email' => 'paula.privada@example.test']);
    ContactInfoModel::query()->create([
        'patient_id' => $patient->id,
        'phone_number' => '+52 744 555 0199',
        'email' => 'contacto.privado@example.test',
        'emergency_contact' => 'Contacto de emergencia privado',
    ]);
    MedicalDataModel::query()->create([
        'patient_id' => $patient->id,
        'blood_type' => 'O+',
        'allergies' => ['Alergia privada a la penicilina'],
        'medications' => ['Medicamento privado diario'],
        'last_dentist_visit' => ['2026-01-15'],
    ]);
    AddressesModel::query()->create([
        'patient_id' => $patient->id,
        'street' => 'Calle Privada 123',
        'city' => 'Acapulco',
        'state' => 'Guerrero',
        'postal_code' => '39300',
    ]);

    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-10-22', 'time' => '12:30']);
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-10-21', 'time' => '10:00']);
    $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-10-20', 'time' => '10:00', 'status' => 'completada']);

    $withFollowUp = $this->createAppointment(['patient_id' => $patient->id, 'date' => '2026-10-22', 'time' => '09:00', 'status' => 'completada']);
    $tracking = AppointmentTrackingModel::query()->create([
        'id' => (string) Str::uuid(),
        'reason' => 'Motivo privado de la consulta',
        'symptoms' => ['Síntoma privado'],
        'diagnosis' => 'Diagnóstico privado',
        'procedure_performed' => 'Procedimiento privado',
        'observations' => 'Observación privada',
        'recommendations' => 'Recomendación privada',
        'appointment_id' => $withFollowUp->id,
    ]);
    AppointmentTrackingPrescriptionModel::query()->create([
        'id' => (string) Str::uuid(),
        'medication' => 'Receta privada',
        'dosage' => 'Dosis privada',
        'duration_days' => 3,
        'daily_frequency' => 2,
        'instructions' => 'Indicación privada',
        'appointment_tracking_id' => $tracking->id,
    ]);

    $this->actingAsAdmin();
});

it('keeps the medical, contact and clinical data of the patient out of today and the unclosed ones (abuse)', function (string $url) {
    $response = $this->getJson($url)->assertOk();
    $content = $response->getContent();

    expect($response->json('data'))->not->toBeEmpty();

    foreach (dashboardPrivateValues() as $value) {
        expect(str_contains($content, $value) || str_contains($content, json_encode($value)))->toBeFalse("La respuesta de {$url} lleva \"{$value}\".");
    }
})->with([
    'hoy' => '/api/v1/agenda/today-summary',
    'sin cerrar' => '/api/v1/agenda/unclosed',
]);

it('keeps the medical, contact and clinical data of the patient out of the ones without follow-up (abuse)', function (string $url) {
    $response = $this->getJson($url)->assertOk();
    $content = $response->getContent();

    expect($response->json('data'))->not->toBeEmpty();

    foreach (dashboardPrivateValues() as $value) {
        expect(str_contains($content, $value) || str_contains($content, json_encode($value)))->toBeFalse("La respuesta de {$url} lleva \"{$value}\".");
    }
})->with([
    'sin seguimiento' => '/api/v1/agenda/without-tracking',
])->skip('020: pendiente de T038');

it('gives each appointment only the keys of the contract', function (string $url, string $path) {
    $items = $this->getJson($url)->assertOk()->json($path);

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect(array_diff(array_keys($item), dashboardContractKeys()))->toBe([]);
    }
})->with([
    'hoy' => ['/api/v1/agenda/today-summary', 'data.appointments'],
    'sin cerrar' => ['/api/v1/agenda/unclosed', 'data.items'],
]);

it('gives each appointment without follow-up only the keys of the contract', function (string $url, string $path) {
    $items = $this->getJson($url)->assertOk()->json($path);

    expect($items)->not->toBeEmpty();

    foreach ($items as $item) {
        expect(array_diff(array_keys($item), dashboardContractKeys()))->toBe([]);
    }
})->with([
    'sin seguimiento' => ['/api/v1/agenda/without-tracking', 'data.items'],
])->skip('020: pendiente de T038');

it('gives the next appointment and the ones after it only the keys of the contract', function () {
    $data = $this->getJson('/api/v1/agenda/today-summary')->assertOk()->json('data');

    expect(array_keys($data))->toEqualCanonicalizing(['date', 'now', 'appointments', 'counts', 'next', 'after', 'after_more'])
        ->and(array_keys($data['next']))->toEqualCanonicalizing(['id', 'time', 'status', 'patient_name', 'treatment_name', 'doctor_name', 'can']);
});
