<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TodayAgenda;
use Illuminate\Support\Str;

/*
 * Spec 020 (CA1, CA2, CA3, D25): the agenda of today. With the day of the conventions (Thursday
 * 22 October 2026 at 11:40, 8 appointments): ordered by time, 3 completed, 4 to attend and 1
 * cancelled, the next at 12:30 and two after it with one more. The next is the first one to attend
 * by time even when its time has passed: it only changes when it is completed or cancelled.
 */

function todayAgendaNow(string $time = '11:40'): DateTimeImmutable
{
    return new DateTimeImmutable("2026-10-22 {$time}:00", new DateTimeZone('America/Mexico_City'));
}

function todayAppointment(string $time, string $status): AppointmentEntity
{
    return AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-22',
        time: $time,
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

/**
 * The 8 appointments of the conventions, out of order on purpose.
 *
 * @return list<AppointmentEntity>
 */
function conventionDay(): array
{
    return [
        todayAppointment('16:00', 'asignada'),
        todayAppointment('09:45', 'completada'),
        todayAppointment('12:30', 'asignada'),
        todayAppointment('10:30', 'cancelada'),
        todayAppointment('17:30', 'asignada'),
        todayAppointment('09:00', 'completada'),
        todayAppointment('13:15', 'reprogramada'),
        todayAppointment('11:15', 'completada'),
    ];
}

/**
 * @param  list<AppointmentEntity>  $appointments
 * @return list<string>
 */
function timesOf(array $appointments): array
{
    return array_map(fn (AppointmentEntity $appointment): string => $appointment->Time()->value, $appointments);
}

it('orders the appointments of today by time', function () {
    $agenda = TodayAgenda::fromAppointments(conventionDay(), todayAgendaNow());

    expect(timesOf($agenda->appointments))->toBe(['09:00', '09:45', '10:30', '11:15', '12:30', '13:15', '16:00', '17:30']);
});

it('counts the day as 8 in total, 3 completed, 4 to attend and 1 cancelled', function () {
    $agenda = TodayAgenda::fromAppointments(conventionDay(), todayAgendaNow());

    expect([$agenda->total, $agenda->completed, $agenda->pending, $agenda->cancelled])->toBe([8, 3, 4, 1]);
});

it('takes the first appointment to attend as the next one, with two after it and one more', function () {
    $agenda = TodayAgenda::fromAppointments(conventionDay(), todayAgendaNow());

    expect($agenda->next?->Time()->value)->toBe('12:30')
        ->and(timesOf($agenda->after))->toBe(['13:15', '16:00'])
        ->and($agenda->afterMore)->toBe(1);
});

it('keeps an appointment to attend whose time has passed as the next one (D25)', function () {
    $appointments = [...conventionDay(), todayAppointment('11:30', 'asignada')];

    $agenda = TodayAgenda::fromAppointments($appointments, todayAgendaNow('11:40'));

    expect($agenda->next?->Time()->value)->toBe('11:30')
        ->and(timesOf($agenda->after))->toBe(['12:30', '13:15'])
        ->and($agenda->afterMore)->toBe(2);
});

it('tells which appointments are already past', function () {
    $agenda = TodayAgenda::fromAppointments(conventionDay(), todayAgendaNow());
    $past = array_values(array_filter($agenda->appointments, fn (AppointmentEntity $appointment): bool => $agenda->isPast($appointment)));

    expect(timesOf($past))->toBe(['09:00', '09:45', '10:30', '11:15']);
});

it('has no next appointment when every appointment of the day is closed', function () {
    $agenda = TodayAgenda::fromAppointments([
        todayAppointment('09:00', 'completada'),
        todayAppointment('12:30', 'cancelada'),
    ], todayAgendaNow());

    expect($agenda->next)->toBeNull()
        ->and($agenda->after)->toBe([])
        ->and($agenda->afterMore)->toBe(0)
        ->and($agenda->pending)->toBe(0);
});

it('handles a day without appointments', function () {
    $agenda = TodayAgenda::fromAppointments([], todayAgendaNow());

    expect($agenda->appointments)->toBe([])
        ->and([$agenda->total, $agenda->completed, $agenda->pending, $agenda->cancelled])->toBe([0, 0, 0, 0])
        ->and($agenda->next)->toBeNull()
        ->and($agenda->after)->toBe([])
        ->and($agenda->afterMore)->toBe(0);
});
