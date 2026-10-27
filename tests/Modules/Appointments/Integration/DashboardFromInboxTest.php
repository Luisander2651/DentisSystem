<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Tests\Support\FakesTwilio;

uses(AppointmentsIntegrationTestCase::class, FakesTwilio::class);

/*
 * Spec 020 (CA5, CA6): an unclosed appointment closed from the dashboard leaves the list. It is
 * completed with POST /complete by the administrator or by its doctor, and cancelled by the
 * assistant with the same operation as the agenda.
 */

const INBOX_UNCLOSED_URL = '/api/v1/agenda/unclosed';

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->fakeTwilio();
});

/**
 * @return array<string, mixed>
 */
function inboxCompletePayload(): array
{
    return [
        'reason' => 'Dolor persistente',
        'symptoms' => ['dolor'],
        'diagnosis' => 'Caries profunda',
        'procedure_performed' => 'Endodoncia',
        'observations' => null,
        'recommendations' => null,
        'prescriptions' => [],
    ];
}

/**
 * @return list<string>
 */
function inboxUnclosedIds(object $test): array
{
    return array_column($test->getJson(INBOX_UNCLOSED_URL)->assertOk()->json('data.items'), 'id');
}

it('takes an unclosed appointment out of the list once the administrator completes it', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-21', 'time' => '10:00']);

    expect(inboxUnclosedIds($this))->toBe([$appointment->id]);

    $this->postJson("/api/v1/appointments/{$appointment->id}/complete", inboxCompletePayload())->assertCreated();

    expect(inboxUnclosedIds($this))->toBe([]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
});

it('takes an unclosed appointment out of the list once its doctor completes it', function () {
    $doctor = $this->actingAsNonAdminUser('Doctor');
    $appointment = $this->createAppointment(['date' => '2026-10-20', 'time' => '12:00', 'user_id' => $doctor->id]);

    expect(inboxUnclosedIds($this))->toBe([$appointment->id]);

    $this->postJson("/api/v1/appointments/{$appointment->id}/complete", inboxCompletePayload())->assertCreated();

    expect(inboxUnclosedIds($this))->toBe([]);
});

it('takes an unclosed appointment out of the list once the assistant cancels it', function () {
    $this->actingAsNonAdminUser('Asistente');
    $appointment = $this->createAppointment(['date' => '2026-10-19', 'time' => '09:00', 'status' => 'reprogramada']);

    expect(inboxUnclosedIds($this))->toBe([$appointment->id]);

    $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'cancelada'])->assertOk();

    expect(inboxUnclosedIds($this))->toBe([]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelada']);
});
