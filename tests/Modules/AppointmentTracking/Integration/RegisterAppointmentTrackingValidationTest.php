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
]);

it('accepts the follow-up without symptoms, as when completing (D23)', function (Closure $payload) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->postJson(registerValidationUrl($appointment->id), $payload->call($this))->assertCreated();

    $this->assertDatabaseHas('appointment_tracking', ['appointment_id' => $appointment->id]);
})->with([
    'sin symptoms' => [fn () => array_diff_key($this->validCompletePayload(), ['symptoms' => true])],
    'symptoms vacío' => [fn () => $this->validCompletePayload(['symptoms' => []])],
]);

/*
 * Spec 020 (R3; limits decided by the user on 2026-10-26): medication and dosage fit their
 * columns, the texts take up to 2000 characters and a follow-up has up to 20 symptoms and 20
 * prescriptions. Over the limit the answer is 422 under the field, never 500.
 */
it('refuses a field over its limit with its text under it', function (Closure $payload, string $field, string $message) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);

    $this->postJson(registerValidationUrl($appointment->id), $payload->call($this))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);

    $this->assertDatabaseMissing('appointment_tracking', ['appointment_id' => $appointment->id]);
})->with([
    'motivo de 2001' => [fn () => $this->validCompletePayload(['reason' => str_repeat('a', 2001)]), 'reason', 'El motivo de la consulta admite hasta 2000 caracteres.'],
    'diagnóstico de 2001' => [fn () => $this->validCompletePayload(['diagnosis' => str_repeat('a', 2001)]), 'diagnosis', 'El diagnóstico admite hasta 2000 caracteres.'],
    'procedimiento de 2001' => [fn () => $this->validCompletePayload(['procedure_performed' => str_repeat('a', 2001)]), 'procedure_performed', 'El procedimiento realizado admite hasta 2000 caracteres.'],
    'observaciones de 2001' => [fn () => $this->validCompletePayload(['observations' => str_repeat('a', 2001)]), 'observations', 'Las observaciones admiten hasta 2000 caracteres.'],
    'recomendaciones de 2001' => [fn () => $this->validCompletePayload(['recommendations' => str_repeat('a', 2001)]), 'recommendations', 'Las recomendaciones admiten hasta 2000 caracteres.'],
    'síntoma de 256' => [fn () => $this->validCompletePayload(['symptoms' => [str_repeat('a', 256)]]), 'symptoms.0', 'Cada síntoma admite hasta 255 caracteres.'],
    '21 síntomas' => [fn () => $this->validCompletePayload(['symptoms' => array_fill(0, 21, 'dolor')]), 'symptoms', 'Escribe hasta 20 síntomas.'],
    'medicamento de 256' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['medication' => str_repeat('a', 256)])]]), 'prescriptions.0.medication', 'El medicamento admite hasta 255 caracteres.'],
    'dosis de 256' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['dosage' => str_repeat('a', 256)])]]), 'prescriptions.0.dosage', 'La dosis admite hasta 255 caracteres.'],
    'indicaciones de 2001' => [fn () => $this->validCompletePayload(['prescriptions' => [$this->validPrescriptionPayload(['instructions' => str_repeat('a', 2001)])]]), 'prescriptions.0.instructions', 'Las indicaciones admiten hasta 2000 caracteres.'],
    '21 recetas' => [fn () => $this->validCompletePayload(['prescriptions' => array_fill(0, 21, $this->validPrescriptionPayload())]), 'prescriptions', 'Añade hasta 20 recetas.'],
])->skip('020: pendiente de T068');

it('accepts every field right at its limit', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada']);
    $text = str_repeat('a', 2000);

    $this->postJson(registerValidationUrl($appointment->id), $this->validCompletePayload([
        'reason' => $text,
        'diagnosis' => $text,
        'procedure_performed' => $text,
        'observations' => $text,
        'recommendations' => $text,
        'symptoms' => array_fill(0, 20, str_repeat('s', 255)),
        'prescriptions' => array_fill(0, 20, $this->validPrescriptionPayload([
            'medication' => str_repeat('m', 255),
            'dosage' => str_repeat('d', 255),
            'instructions' => $text,
        ])),
    ]))->assertCreated();

    $this->assertDatabaseCount('appointment_tracking_prescriptions', 20);
});
