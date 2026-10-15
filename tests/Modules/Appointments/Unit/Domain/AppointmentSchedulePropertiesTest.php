<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Exceptions\AppointmentInThePastException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use App\Modules\Users\Domain\ValueObjects\UserId;
use Illuminate\Support\Str;
use Tests\Support\UsesEris;

uses(UsesEris::class);

/*
 * Spec 017, CA3 and CA4: for any offset in minutes from "now", a moment at or before it is
 * refused and a later one is accepted, both when creating and when rescheduling.
 */

function propertyNow(): DateTimeImmutable
{
    return new DateTimeImmutable('2026-10-15 10:00:00', new DateTimeZone('America/Mexico_City'));
}

/**
 * @return array{0: AppointmentDate, 1: AppointmentTime}
 */
function momentAtOffset(int $minutes): array
{
    $moment = propertyNow()->modify(sprintf('%+d minutes', $minutes));

    return [AppointmentDate::fromString($moment->format('Y-m-d')), AppointmentTime::fromString($moment->format('H:i'))];
}

function refusesAsPast(Closure $change): bool
{
    try {
        $change();
    } catch (AppointmentInThePastException) {
        return true;
    }

    return false;
}

function assignedAppointment(): AppointmentEntity
{
    return AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: '2026-10-20',
        time: '10:00',
        whatsappReminder: false,
        status: 'asignada',
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

it('refuses to reschedule to any moment at or before now (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 400), Eris\Generator\choose(0, 1439))->then(function (int $days, int $minutes) {
        [$date, $time] = momentAtOffset(-($days * 1440 + $minutes));

        expect(refusesAsPast(fn () => assignedAppointment()->reschedule($date, $time, propertyNow())))->toBeTrue();
    });
});

it('reschedules to any moment after now (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 400), Eris\Generator\choose(1, 1440))->then(function (int $days, int $minutes) {
        [$date, $time] = momentAtOffset($days * 1440 + $minutes);
        $appointment = assignedAppointment();

        $appointment->reschedule($date, $time, propertyNow());

        expect($appointment->Status()->value)->toBe('reprogramada');
    });
});

it('refuses to create an appointment at any moment at or before now (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 400), Eris\Generator\choose(0, 1439))->then(function (int $days, int $minutes) {
        [$date, $time] = momentAtOffset(-($days * 1440 + $minutes));

        expect(refusesAsPast(fn () => AppointmentEntity::create($date, $time, TreatmentId::fromInt(1), new UserId((string) Str::uuid()), new PatientId((string) Str::uuid()), propertyNow())))->toBeTrue();
    });
});

it('creates an appointment at any moment after now (property)', function () {
    $this->forAll(Eris\Generator\choose(0, 400), Eris\Generator\choose(1, 1440))->then(function (int $days, int $minutes) {
        [$date, $time] = momentAtOffset($days * 1440 + $minutes);

        $appointment = AppointmentEntity::create($date, $time, TreatmentId::fromInt(1), new UserId((string) Str::uuid()), new PatientId((string) Str::uuid()), propertyNow());

        expect($appointment->Status()->value)->toBe('asignada');
    });
});
