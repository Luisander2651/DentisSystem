<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetPatientAppointmentsDTO;
use App\Modules\Appointments\Aplication\UseCases\GetPatientAppointmentsUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA3, CA13): the history of the portal belongs to the patient of the session, who is
 * asked for before anything is read, and comes from the most recent appointment to the oldest.
 */

const PORTAL_HISTORY_PATIENT_ID = '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f';

final class PortalHistoryCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

function portalHistoryAuthorization(PortalHistoryCallLog $log, bool $isPatient): AuthorizationServiceInterface
{
    return new class($log, $isPatient) implements AuthorizationServiceInterface
    {
        public function __construct(private PortalHistoryCallLog $log, private bool $isPatient) {}

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

            return PORTAL_HISTORY_PATIENT_ID;
        }
    };
}

function portalHistoryAppointment(string $id, string $date, string $time): AppointmentEntity
{
    return AppointmentEntity::fromPrimitives(
        id: $id,
        date: $date,
        time: $time,
        whatsappReminder: false,
        status: 'asignada',
        treatmentId: '1',
        userId: '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f',
        patientId: PORTAL_HISTORY_PATIENT_ID,
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
function portalHistoryUseCase(PortalHistoryCallLog $log, bool $isPatient, array $appointments): GetPatientAppointmentsUseCase
{
    $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
    $repository->shouldReceive('findAllByStatusAndDateOrPatientId')
        ->andReturnUsing(function ($status, $date, ?PatientId $patientId) use ($log, $appointments): array {
            $log->calls[] = 'repository:'.$patientId?->value;

            return $appointments;
        });

    return new GetPatientAppointmentsUseCase(portalHistoryAuthorization($log, $isPatient), new AppointmentsService($repository));
}

it('asks for the patient of the session first and reads only their appointments', function () {
    $log = new PortalHistoryCallLog;

    portalHistoryUseCase($log, true, [])->execute(GetPatientAppointmentsDTO::create());

    expect($log->calls)->toBe(['patient', 'repository:'.PORTAL_HISTORY_PATIENT_ID]);
});

it('reads nothing when the actor is not an active patient (abuse)', function () {
    $log = new PortalHistoryCallLog;

    expect(fn () => portalHistoryUseCase($log, false, [])->execute(GetPatientAppointmentsDTO::create()))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe(['patient']);
});

it('orders the appointments from the most recent to the oldest, by date and then by time', function () {
    $log = new PortalHistoryCallLog;
    $appointments = [
        portalHistoryAppointment('aaaaaaaa-0000-4000-8000-000000000001', '2026-09-14', '16:30'),
        portalHistoryAppointment('aaaaaaaa-0000-4000-8000-000000000002', '2026-11-26', '12:00'),
        portalHistoryAppointment('aaaaaaaa-0000-4000-8000-000000000003', '2026-10-20', '09:00'),
        portalHistoryAppointment('aaaaaaaa-0000-4000-8000-000000000004', '2026-11-12', '10:30'),
        portalHistoryAppointment('aaaaaaaa-0000-4000-8000-000000000005', '2026-11-12', '17:00'),
    ];

    $result = portalHistoryUseCase($log, true, $appointments)->execute(GetPatientAppointmentsDTO::create());

    expect(array_map(fn (AppointmentEntity $a): string => $a->Date()->value.' '.$a->Time()->value, $result))->toBe([
        '2026-11-26 12:00',
        '2026-11-12 17:00',
        '2026-11-12 10:30',
        '2026-10-20 09:00',
        '2026-09-14 16:30',
    ]);
});
