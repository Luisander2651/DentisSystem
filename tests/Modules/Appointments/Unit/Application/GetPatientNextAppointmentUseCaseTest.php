<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetPatientNextAppointmentDTO;
use App\Modules\Appointments\Aplication\UseCases\GetPatientNextAppointmentUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA1, CA2): the next appointment is the assigned or rescheduled one nearest from today
 * on, in the clinic's time zone. One of today counts even when its hour has gone by, so a patient
 * who arrives late still has their QR code. The patient is the one of the session.
 */

const NEXT_APPOINTMENT_PATIENT_ID = '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f';
const NEXT_APPOINTMENT_TODAY = '2026-09-17';

final class NextAppointmentCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

function nextAppointmentAuthorization(NextAppointmentCallLog $log, bool $isPatient): AuthorizationServiceInterface
{
    return new class($log, $isPatient) implements AuthorizationServiceInterface
    {
        public function __construct(private NextAppointmentCallLog $log, private bool $isPatient) {}

        public function assertCan(string $permission): void
        {
            throw AuthorizationException::forbidden($permission);
        }

        public function can(string $permission): bool
        {
            return false;
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            $this->log->calls[] = 'patient';

            if (! $this->isPatient) {
                throw AuthorizationException::unauthenticated();
            }

            return NEXT_APPOINTMENT_PATIENT_ID;
        }
    };
}

function nextAppointment(string $date, string $time, string $status): AppointmentEntity
{
    return AppointmentEntity::fromPrimitives(
        id: (string) Str::uuid(),
        date: $date,
        time: $time,
        whatsappReminder: false,
        status: $status,
        treatmentId: '1',
        userId: '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f',
        patientId: NEXT_APPOINTMENT_PATIENT_ID,
        treatmentName: null,
        userFirstName: null,
        userLastName: null,
        patientName: null,
        treatmentTime: null,
        createdAt: '2026-09-01 09:00:00',
        updatedAt: '2026-09-01 09:00:00',
    );
}

/**
 * @param  list<AppointmentEntity>  $appointments
 */
function nextAppointmentUseCase(NextAppointmentCallLog $log, array $appointments, bool $isPatient = true): GetPatientNextAppointmentUseCase
{
    $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
    $repository->shouldReceive('findAllByStatusAndDateOrPatientId')
        ->andReturnUsing(function ($status, $date, ?PatientId $patientId) use ($log, $appointments): array {
            $log->calls[] = 'repository:'.$patientId?->value;

            return $appointments;
        });

    return new GetPatientNextAppointmentUseCase(nextAppointmentAuthorization($log, $isPatient), new AppointmentsService($repository));
}

/**
 * @param  list<AppointmentEntity>  $appointments
 */
function nextAppointmentOf(array $appointments): ?string
{
    $next = nextAppointmentUseCase(new NextAppointmentCallLog, $appointments)
        ->execute(GetPatientNextAppointmentDTO::create(NEXT_APPOINTMENT_TODAY));

    return $next === null ? null : $next->Date()->value.' '.$next->Time()->value;
}

it('asks for the patient of the session first and reads only their appointments', function () {
    $log = new NextAppointmentCallLog;

    nextAppointmentUseCase($log, [])->execute(GetPatientNextAppointmentDTO::create(NEXT_APPOINTMENT_TODAY));

    expect($log->calls)->toBe(['patient', 'repository:'.NEXT_APPOINTMENT_PATIENT_ID]);
});

it('reads nothing when the actor is not an active patient (abuse)', function () {
    $log = new NextAppointmentCallLog;

    expect(fn () => nextAppointmentUseCase($log, [], false)->execute(GetPatientNextAppointmentDTO::create(NEXT_APPOINTMENT_TODAY)))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe(['patient']);
});

it('takes an assigned appointment of today even when its hour has gone by', function () {
    expect(nextAppointmentOf([nextAppointment('2026-09-17', '09:00', 'asignada')]))->toBe('2026-09-17 09:00');
});

it('leaves out an open appointment of yesterday', function () {
    expect(nextAppointmentOf([nextAppointment('2026-09-16', '18:00', 'asignada')]))->toBeNull();
});

it('leaves out a completed or cancelled appointment of today', function (string $status) {
    expect(nextAppointmentOf([nextAppointment('2026-09-17', '16:00', $status)]))->toBeNull();
})->with(['completada', 'cancelada']);

it('takes the one of today before the one of tomorrow', function () {
    expect(nextAppointmentOf([
        nextAppointment('2026-09-18', '09:00', 'asignada'),
        nextAppointment('2026-09-17', '16:00', 'asignada'),
    ]))->toBe('2026-09-17 16:00');
});

it('takes a rescheduled appointment of tomorrow before an assigned one after it', function () {
    expect(nextAppointmentOf([
        nextAppointment('2026-09-19', '08:00', 'asignada'),
        nextAppointment('2026-09-18', '12:00', 'reprogramada'),
    ]))->toBe('2026-09-18 12:00');
});

it('gives nothing to a patient without appointments', function () {
    expect(nextAppointmentOf([]))->toBeNull();
});
