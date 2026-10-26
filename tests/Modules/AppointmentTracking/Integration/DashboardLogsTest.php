<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\AppointmentTracking\Integration\AppointmentTrackingIntegrationTestCase;

uses(AppointmentTrackingIntegrationTestCase::class);

/*
 * Spec 020 (CA7, CA8, A7, P6): what the dashboard does leaves in no log the name, the phone or
 * the email of the patient, nor the reason, the diagnosis or the prescriptions of the follow-up:
 * registering a follow-up, cancelling an unclosed appointment, and an unexpected error in each
 * new read, even when the failure carries those data in its message.
 */

const DASHBOARD_LOGS_SECRETS = [
    'Rosalba', 'Quintanar', 'rosalba.quintanar@example.test', '+52 744 555 0142',
    'Motivo reservado de la consulta', 'Diagnóstico reservado', 'Medicamento reservado',
];

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $this->logged->push($entry));

    $patient = $this->createPatient(['first_name' => 'Rosalba', 'last_name' => 'Quintanar', 'email' => 'rosalba.quintanar@example.test']);
    ContactInfoModel::query()->create(['patient_id' => $patient->id, 'phone_number' => '+52 744 555 0142', 'email' => 'rosalba.quintanar@example.test']);
    $this->patientId = $patient->id;
});

function dashboardLoggedText(Collection $logged): string
{
    return $logged
        ->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context, JSON_UNESCAPED_UNICODE))
        ->implode("\n");
}

function dashboardLogsLeakNothing(Collection $logged): void
{
    $text = dashboardLoggedText($logged);

    foreach (DASHBOARD_LOGS_SECRETS as $secret) {
        expect(str_contains($text, $secret))->toBeFalse("Un log lleva \"{$secret}\".");
    }
}

function dashboardLogsFailure(): RuntimeException
{
    return new RuntimeException('Falló con '.implode(', ', DASHBOARD_LOGS_SECRETS));
}

it('logs no patient nor clinical data when registering a follow-up', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'completada', 'patient_id' => $this->patientId]);

    $this->postJson("/api/v1/appointments/{$appointment->id}/tracking", [
        'reason' => 'Motivo reservado de la consulta',
        'symptoms' => ['dolor'],
        'diagnosis' => 'Diagnóstico reservado',
        'procedure_performed' => 'Resina',
        'prescriptions' => [$this->validPrescriptionPayload(['medication' => 'Medicamento reservado'])],
    ])->assertCreated();

    dashboardLogsLeakNothing($this->logged);
})->skip('020: pendiente de T041');

it('logs no patient data when cancelling an unclosed appointment', function () {
    $this->actingAsNonAdminUser('Asistente');
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'status' => 'asignada', 'patient_id' => $this->patientId]);

    $this->putJson("/api/v1/appointments/{$appointment->id}", ['status' => 'cancelada'])->assertOk();

    expect(AppointmentModel::query()->find($appointment->id)->status)->toBe('cancelada');
    dashboardLogsLeakNothing($this->logged);
});

it('logs an unexpected error of today and the unclosed ones once, without the data of its message', function (string $url, string $method) {
    $this->actingAsAdmin();
    $this->mock(AppointmentsRepositoryInterface::class)->shouldReceive($method)->andThrow(dashboardLogsFailure());

    $this->getJson($url)->assertStatus(500);

    expect($this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error'))->toHaveCount(1);
    dashboardLogsLeakNothing($this->logged);
})->with([
    'hoy' => ['/api/v1/agenda/today-summary', 'findAllByStatusAndDateOrPatientId'],
    'sin cerrar' => ['/api/v1/agenda/unclosed', 'findUnclosedBefore'],
]);

it('logs an unexpected error of the ones without follow-up once, without the data of its message', function () {
    $this->actingAsAdmin();
    $this->mock(PendingTrackingRepositoryInterface::class)->shouldReceive('findCompletedWithoutTracking')->andThrow(dashboardLogsFailure());

    $this->getJson('/api/v1/agenda/without-tracking')->assertStatus(500);

    expect($this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error'))->toHaveCount(1);
    dashboardLogsLeakNothing($this->logged);
});
