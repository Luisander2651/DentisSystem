<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * Spec 020 (CA8, A19, A30, A40, D23): POST /appointments/{id}/tracking answers 422 with the
 * Spanish text of the contract under each field, the same ones as the dialog to complete.
 * "Escribe el síntoma o quítalo." is new in the API. Symptoms are optional, as when completing.
 */

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
});

function registerValidationUrl(string $appointmentId): string
{
    return "/api/v1/appointments/{$appointmentId}/tracking";
}

it('refuses a missing or out of range field with the text of the contract under it', function (Closure $payload, string $field, string $message) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->postJson(registerValidationUrl($appointment->id), $payload->call($this))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);

    $this->assertDatabaseMissing('appointment_tracking', ['appointment_id' => $appointment->id]);
})->with([
    'motivo vacío' => [fn () => $this->validCompletePayload(['reason' => '']), 'reason', 'El motivo de la consulta es obligatorio.'],
    'motivo con solo espacios' => [fn () => $this->validCompletePayload(['reason' => '   ']), 'reason', 'El motivo de la consulta es obligatorio.'],
    'diagnóstico vacío' => [fn () => $this->validCompletePayload(['diagnosis' => '']), 'diagnosis', 'El diagnóstico es obligatorio.'],
    'procedimiento vacío' => [fn () => $this->validCompletePayload(['procedure_performed' => '']), 'procedure_performed', 'El procedimiento realizado es obligatorio.'],
    'un síntoma vacío' => [fn () => $this->validCompletePayload(['symptoms' => ['', 'dolor']]), 'symptoms.0', 'Escribe el síntoma o quítalo.'],
    'receta sin medicamento' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['medication' => ''])]]), 'prescriptions.0.medication', 'Escribe el medicamento.'],
    'receta sin dosis' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['dosage' => ''])]]), 'prescriptions.0.dosage', 'Escribe la dosis.'],
    'duración 0' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['durationDays' => 0])]]), 'prescriptions.0.durationDays', 'Escribe una duración de 1 a 365 días.'],
    'duración 366' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['durationDays' => 366])]]), 'prescriptions.0.durationDays', 'Escribe una duración de 1 a 365 días.'],
    'frecuencia 0' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['dailyFrequency' => 0])]]), 'prescriptions.0.dailyFrequency', 'Escribe de 1 a 24 veces al día.'],
    'frecuencia 25' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['dailyFrequency' => 25])]]), 'prescriptions.0.dailyFrequency', 'Escribe de 1 a 24 veces al día.'],
])->skip('020: pendiente de T041');

it('accepts the follow-up without symptoms, as when completing (D23)', function (Closure $payload) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->postJson(registerValidationUrl($appointment->id), $payload->call($this))->assertCreated();

    $this->assertDatabaseHas('appointment_tracking', ['appointment_id' => $appointment->id]);
})->with([
    'sin symptoms' => [fn () => array_diff_key($this->validCompletePayload(), ['symptoms' => true])],
    'symptoms vacío' => [fn () => $this->validCompletePayload(['symptoms' => []])],
])->skip('020: pendiente de T041');
