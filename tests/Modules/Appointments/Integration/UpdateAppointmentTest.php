<?php

declare(strict_types=1);

use App\Modules\Appointments\Aplication\DTOs\UpdateAppointmentDTO;
use App\Modules\Appointments\Aplication\Exceptions\RescheduleAndStatusTogetherException;
use App\Modules\Appointments\Aplication\UseCases\UpdateAppointmentUseCase;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 017: the clock is fixed at 2026-10-15 10:00 in the clinic's timezone, so "the past" and
 * "the future" of every case below do not depend on the day the suite runs.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'America/Mexico_City'));
});

const CLOSED_APPOINTMENT_MESSAGE = 'La cita ya está completada o cancelada y no se puede cambiar.';

it('rejects a reschedule sent with status asignada, as the agenda dialog did (ficha 2026-10-15)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-10-17',
        'time' => '11:00',
        'status' => 'asignada',
        'whatsapp_reminder' => false,
    ]);

    $response->assertUnprocessable();
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'status' => 'asignada']);
});

it('reschedules an appointment successfully', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-10-17',
        'time' => '11:00',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'date' => '2026-10-17',
        'time' => '11:00:00',
        'status' => 'reprogramada',
    ]);
});

it('reschedules to later today', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['date' => '2026-10-15', 'time' => '10:01']);

    $response->assertOk();
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-15', 'time' => '10:01:00', 'status' => 'reprogramada']);
});

it('applies the reminder sent with a reschedule', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00', 'whatsapp_reminder' => false]);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-10-17',
        'time' => '11:00',
        'whatsapp_reminder' => true,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'date' => '2026-10-17',
        'status' => 'reprogramada',
        'whatsapp_reminder' => true,
    ]);
});

it('allows an administrator to reschedule an appointment of a doctor other than themselves', function () {
    $this->actingAsAdmin();
    $doctor = $this->createUserWithRole('Doctor');
    $appointment = $this->createAppointment(['user_id' => $doctor->id, 'date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-10-17',
        'time' => '11:00',
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'user_id' => $doctor->id,
        'date' => '2026-10-17',
    ]);
});

it('returns 409 when rescheduling into a slot occupied by another appointment (post-fix behavior)', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-10-16', 'time' => '09:00']);
    $toReschedule = $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-10-16', 'time' => '12:00']);

    $response = $this->putJson($this->appointmentUrl($toReschedule->id), [
        'date' => '2026-10-16',
        'time' => '09:15',
    ]);

    $response->assertStatus(409);
});

it('rescheduling to its own current slot does not conflict with itself', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment(['time' => 30]);
    $appointment = $this->createAppointment(['treatment_id' => $treatment->id, 'date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), [
        'date' => '2026-10-16',
        'time' => '10:00',
    ]);

    $response->assertStatus(200);
});

it('rejects a reschedule without date or without time and leaves the appointment unchanged', function (array $payload, string $field) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), $payload);

    $response->assertUnprocessable()
        ->assertJsonPath("errors.{$field}.0", 'Necesitas proporcionar fecha y hora para reprogramar la cita.');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'time' => '10:00:00', 'status' => 'asignada']);
})->with([
    'date without time' => [['date' => '2026-10-20'], 'time'],
    'time without date' => [['time' => '15:00'], 'date'],
    'both empty, as the dialog sends them cleared' => [['date' => '', 'time' => ''], 'date'],
    'both null' => [['date' => null, 'time' => null], 'date'],
]);

it('rejects a reschedule to a moment that already passed and leaves the appointment unchanged', function (string $date, string $time) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['date' => $date, 'time' => $time]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'No puedes reprogramar la cita a una fecha u hora que ya pasó.')
        ->assertJsonPath('errors.date.0', 'No puedes reprogramar la cita a una fecha u hora que ya pasó.');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'time' => '10:00:00', 'status' => 'asignada']);
})->with([
    'yesterday' => ['2026-10-14', '12:00'],
    'today, earlier' => ['2026-10-15', '09:59'],
    'exactly now' => ['2026-10-15', '10:00'],
]);

it('rejects a request that reschedules and changes the status at once', function (array $payload) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), $payload);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'No puedes reprogramar y cambiar el estado de la cita a la vez.');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'status' => 'asignada']);
})->with([
    'date, time and cancel' => [['date' => '2026-10-17', 'time' => '11:00', 'status' => 'cancelada']],
    'date and complete' => [['date' => '2026-10-17', 'status' => 'completada']],
    'time and cancel' => [['time' => '11:00', 'status' => 'cancelada']],
]);

it('refuses in the use case a reschedule that travels with a status, without the form request', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    expect(fn () => app(UpdateAppointmentUseCase::class)->execute(
        UpdateAppointmentDTO::create($appointment->id, '2026-10-17', '11:00', null, 'cancelada'),
    ))->toThrow(RescheduleAndStatusTogetherException::class, 'No puedes reprogramar y cambiar el estado de la cita a la vez.');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'status' => 'asignada']);
});

it('answers the refusal to reschedule and change the status at once as an input error of the status', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findById')->andThrow(new RescheduleAndStatusTogetherException('No puedes reprogramar y cambiar el estado de la cita a la vez.'));

    $this->putJson($this->appointmentUrl($appointment->id), ['date' => '2026-10-17', 'time' => '11:00'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'No puedes reprogramar y cambiar el estado de la cita a la vez.');
});

it('rejects a status other than completada or cancelada', function (string $status) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => $status]);

    $response->assertUnprocessable()
        ->assertJsonPath('errors.status.0', 'El estado solo puede cambiar a completada o cancelada.');
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'asignada']);
})->with(['asignada', 'reprogramada']);

it('rejects input with an invalid format', function (array $payload, string $field, ?string $message) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors([$field]);
    if ($message !== null) {
        $response->assertJsonPath("errors.{$field}.0", $message);
    }
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'date' => '2026-10-16', 'status' => 'asignada']);
})->with([
    'date in another format' => [['date' => '17/10/2026', 'time' => '11:00'], 'date', 'La fecha no tiene un formato válido.'],
    'time in another format' => [['date' => '2026-10-17', 'time' => '11h'], 'time', 'La hora no tiene un formato válido.'],
    'reminder that is not a boolean' => [['whatsapp_reminder' => 'quizá'], 'whatsapp_reminder', null],
]);

it('returns 409 when no fields are provided', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), []);

    $response->assertStatus(409)
        ->assertJson(['error' => 'At least one field must be provided for update.']);
});

it('completes an appointment via status field', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'completada']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completada']);
});

it('cancels an assigned or a rescheduled appointment via status field', function (string $status) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => $status]);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'cancelada']);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelada']);
})->with(['asignada', 'reprogramada']);

it('applies the reminder sent with a status change', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['whatsapp_reminder' => false]);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'cancelada', 'whatsapp_reminder' => true]);

    $response->assertOk();
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelada', 'whatsapp_reminder' => true]);
});

it('refuses to reschedule or cancel a completed or cancelled appointment', function (string $status, array $payload) {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => $status, 'date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), $payload);

    $response->assertStatus(409)
        ->assertJson(['error' => CLOSED_APPOINTMENT_MESSAGE]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => $status, 'date' => '2026-10-16', 'time' => '10:00:00']);
})->with([
    'reschedule a completed one' => ['completada', ['date' => '2026-10-17', 'time' => '11:00']],
    'reschedule a cancelled one' => ['cancelada', ['date' => '2026-10-17', 'time' => '11:00']],
    'cancel a completed one' => ['completada', ['status' => 'cancelada']],
    'cancel a cancelled one' => ['cancelada', ['status' => 'cancelada']],
]);

it('refuses to complete a cancelled appointment', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => 'cancelada']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'completada']);

    $response->assertStatus(409)
        ->assertJson(['error' => CLOSED_APPOINTMENT_MESSAGE]);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelada']);
});

it('answers a closed appointment before a date in the past (plan D8)', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['status' => 'cancelada', 'date' => '2026-10-16', 'time' => '10:00']);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['date' => '2026-10-14', 'time' => '12:00']);

    $response->assertStatus(409)
        ->assertJson(['error' => CLOSED_APPOINTMENT_MESSAGE]);
});

it('toggles the whatsapp reminder flag', function () {
    $this->actingAsAdmin();
    $appointment = $this->createAppointment(['whatsapp_reminder' => false]);

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['whatsapp_reminder' => true]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'whatsapp_reminder' => true]);
});

it('returns 409 when the appointment does not exist', function () {
    $this->actingAsAdmin();

    $response = $this->putJson($this->appointmentUrl((string) Str::uuid()), ['status' => 'completada']);

    $response->assertStatus(409);
});

it('rejects unauthenticated request', function () {
    $appointment = $this->createAppointment();

    $response = $this->putJson($this->appointmentUrl($appointment->id), ['status' => 'completada']);

    $response->assertStatus(401);
});
