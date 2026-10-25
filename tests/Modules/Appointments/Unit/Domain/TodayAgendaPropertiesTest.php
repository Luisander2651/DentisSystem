<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\ValueObjects\TodayAgenda;
use Eris\Generator;
use Illuminate\Support\Str;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 020 (CA2, CA3, D25): for any day of appointments and any "now", the counters add up to
 * the total, there is a next appointment exactly when one is still to attend, it is the first one
 * to attend by time whatever "now" is, and at most two come after it.
 */

/**
 * @param  list<array{0: int, 1: string}>  $lines  minutes after midnight and status
 * @return list<AppointmentEntity>
 */
function agendaPropertyAppointments(array $lines): array
{
    return array_map(fn (array $line): AppointmentEntity => AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-22',
        time: sprintf('%02d:%02d', intdiv($line[0], 60), $line[0] % 60),
        whatsappReminder: false,
        status: $line[1],
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
    ), $lines);
}

function agendaPropertyNow(int $minutes): DateTimeImmutable
{
    return (new DateTimeImmutable('2026-10-22 00:00:00', new DateTimeZone('America/Mexico_City')))->modify("+{$minutes} minutes");
}

function agendaPropertyIsToAttend(AppointmentEntity $appointment): bool
{
    return in_array($appointment->Status()->value, ['asignada', 'reprogramada'], true);
}

function agendaPropertyDay(): Generator
{
    return Eris\Generator\seq(Eris\Generator\tuple(
        Eris\Generator\choose(0, 1439),
        Eris\Generator\elements('asignada', 'reprogramada', 'completada', 'cancelada'),
    ));
}

it('adds completed, to attend and cancelled up to the total (property)', function () {
    $this->forAll(agendaPropertyDay(), Eris\Generator\choose(0, 1439))->then(function (array $lines, int $now) {
        $agenda = TodayAgenda::fromAppointments(agendaPropertyAppointments($lines), agendaPropertyNow($now));

        expect($agenda->total)->toBe(count($lines))
            ->and($agenda->completed + $agenda->pending + $agenda->cancelled)->toBe($agenda->total);
    });
})->skip('020: pendiente de T030');

it('has a next appointment only while one is to attend, the first one by time whatever now is (property)', function () {
    $this->forAll(agendaPropertyDay(), Eris\Generator\choose(0, 1439))->then(function (array $lines, int $now) {
        $appointments = agendaPropertyAppointments($lines);
        $agenda = TodayAgenda::fromAppointments($appointments, agendaPropertyNow($now));
        $toAttend = array_values(array_filter($appointments, agendaPropertyIsToAttend(...)));

        expect($agenda->next !== null)->toBe($toAttend !== []);

        if ($agenda->next !== null) {
            $earliest = min(array_map(fn (AppointmentEntity $appointment): string => $appointment->Time()->value, $toAttend));

            expect(agendaPropertyIsToAttend($agenda->next))->toBeTrue()
                ->and($agenda->next->Time()->value)->toBe($earliest);
        }
    });
})->skip('020: pendiente de T030');

it('lists at most two appointments to attend after the next one and counts the rest (property)', function () {
    $this->forAll(agendaPropertyDay(), Eris\Generator\choose(0, 1439))->then(function (array $lines, int $now) {
        $agenda = TodayAgenda::fromAppointments(agendaPropertyAppointments($lines), agendaPropertyNow($now));

        expect(count($agenda->after))->toBeLessThanOrEqual(2)
            ->and($agenda->afterMore)->toBe(max(0, $agenda->pending - 1 - count($agenda->after)));

        foreach ($agenda->after as $appointment) {
            expect(agendaPropertyIsToAttend($appointment))->toBeTrue()
                ->and($appointment->Time()->value >= $agenda->next?->Time()->value)->toBeTrue();
        }
    });
})->skip('020: pendiente de T030');
