<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\AppointmentException;
use App\Modules\Appointments\Domain\Exceptions\AppointmentInThePastException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Users\Domain\ValueObjects\UserId;
use Illuminate\Support\Str;

/*
 * Spec 017: the entity receives "now" and refuses any moment equal to or earlier than it (CA3,
 * CA4), and a closed appointment can no longer be moved, cancelled or, once cancelled,
 * completed (CA8, CA11).
 */

const CLINIC_NOW = '2026-10-15 10:00:00';

function clinicNow(string $moment = CLINIC_NOW): DateTimeImmutable
{
    return new DateTimeImmutable($moment, new DateTimeZone('America/Mexico_City'));
}

function appointmentWithStatus(string $status): AppointmentEntity
{
    return AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-20',
        time: '10:00',
        whatsappReminder: false,
        status: $status,
        treatmentId: '1',
        userId: (string) Str::uuid(),
        patientId: (string) Str::uuid(),
        treatmentName: null,
        userFirstName: null,
        userLastName: null,
        patientName: null,
        treatmentTime: null,
        createdAt: '2026-10-01 09:00:00',
        updatedAt: '2026-10-01 09:00:00',
    );
}

function newAppointmentAt(string $date, string $time, DateTimeImmutable $now): AppointmentEntity
{
    return AppointmentEntity::create(
        AppointmentDate::fromString($date),
        AppointmentTime::fromString($time),
        TreatmentId::fromInt(1),
        new UserId((string) Str::uuid()),
        new PatientId((string) Str::uuid()),
        $now,
    );
}

it('refuses to reschedule to a moment that already passed', function (string $date, string $time) {
    $appointment = appointmentWithStatus('asignada');

    expect(fn () => $appointment->reschedule(AppointmentDate::fromString($date), AppointmentTime::fromString($time), clinicNow()))
        ->toThrow(AppointmentInThePastException::class, 'No puedes reprogramar la cita a una fecha u hora que ya pasó.');
    expect($appointment->Date()->value)->toBe('2026-10-20')
        ->and($appointment->Status()->value)->toBe('asignada');
})->with([
    'yesterday' => ['2026-10-14', '12:00'],
    'today, earlier' => ['2026-10-15', '09:59'],
    'exactly now' => ['2026-10-15', '10:00'],
])->skip('017: pendiente de T021');

it('reschedules to a future moment and leaves the appointment rescheduled', function (string $status, string $date, string $time) {
    $appointment = appointmentWithStatus($status);

    $appointment->reschedule(AppointmentDate::fromString($date), AppointmentTime::fromString($time), clinicNow());

    expect($appointment->Date()->value)->toBe($date)
        ->and($appointment->Time()->value)->toBe($time)
        ->and($appointment->Status()->value)->toBe('reprogramada');
})->with([
    'assigned, later today' => ['asignada', '2026-10-15', '10:01'],
    'assigned, tomorrow' => ['asignada', '2026-10-16', '09:00'],
    'rescheduled, tomorrow' => ['reprogramada', '2026-10-16', '09:00'],
]);

it('compares in the clinic timezone, where 23:45 is still later that day', function () {
    $appointment = appointmentWithStatus('asignada');

    $appointment->reschedule(AppointmentDate::fromString('2026-10-15'), AppointmentTime::fromString('23:45'), clinicNow('2026-10-15 23:30:00'));

    expect($appointment->Date()->value)->toBe('2026-10-15')
        ->and($appointment->Status()->value)->toBe('reprogramada');
});

it('refuses to reschedule or cancel a closed appointment', function (string $status, string $action) {
    $appointment = appointmentWithStatus($status);

    $change = $action === 'reschedule'
        ? fn () => $appointment->reschedule(AppointmentDate::fromString('2026-10-21'), AppointmentTime::fromString('11:00'), clinicNow())
        : fn () => $appointment->cancel();

    expect($change)->toThrow(AppointmentException::class, 'La cita ya está completada o cancelada y no se puede cambiar.');
    expect($appointment->Status()->value)->toBe($status)
        ->and($appointment->Date()->value)->toBe('2026-10-20');
})->with([
    'reschedule a completed one' => ['completada', 'reschedule'],
    'reschedule a cancelled one' => ['cancelada', 'reschedule'],
    'cancel a completed one' => ['completada', 'cancel'],
    'cancel a cancelled one' => ['cancelada', 'cancel'],
])->skip('017: pendiente de T021');

it('cancels an assigned or a rescheduled appointment', function (string $status) {
    $appointment = appointmentWithStatus($status);

    $appointment->cancel();

    expect($appointment->Status()->value)->toBe('cancelada');
})->with(['asignada', 'reprogramada']);

it('refuses to complete a cancelled appointment', function () {
    $appointment = appointmentWithStatus('cancelada');

    expect(fn () => $appointment->complete())
        ->toThrow(AppointmentException::class, 'La cita ya está completada o cancelada y no se puede cambiar.');
    expect($appointment->Status()->value)->toBe('cancelada');
})->skip('017: pendiente de T021');

it('completes an assigned, a rescheduled or an already completed appointment', function (string $status) {
    $appointment = appointmentWithStatus($status);

    $appointment->complete();

    expect($appointment->Status()->value)->toBe('completada');
})->with(['asignada', 'reprogramada', 'completada']);

it('refuses to create an appointment in the past', function (string $date, string $time) {
    expect(fn () => newAppointmentAt($date, $time, clinicNow()))
        ->toThrow(AppointmentInThePastException::class, 'No puedes agendar una cita en una fecha u hora que ya pasó.');
})->with([
    'yesterday' => ['2026-10-14', '12:00'],
    'today, earlier' => ['2026-10-15', '09:59'],
    'exactly now' => ['2026-10-15', '10:00'],
])->skip('017: pendiente de T021');

it('creates an assigned appointment in the future', function () {
    $appointment = newAppointmentAt('2026-10-15', '10:01', clinicNow());

    expect($appointment->Status()->value)->toBe('asignada');
});
