<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\AppointmentTracking\Application\DTOs\GetAppointmentTrackingByAppointmentIdDTO;
use App\Modules\AppointmentTracking\Application\UseCases\GetPatientPrescriptionsUseCase;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Entities\PrescriptionEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\PrescriptionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use App\Modules\AppointmentTracking\Domain\Service\PrescriptionService;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA5, CA6, CA8, CA12, CA17): the patient of the session reads the recommendations and
 * prescriptions of their completed appointments. Someone else's appointment reads as missing,
 * whatever its status; an open or cancelled one, or one without follow-up, gives nothing.
 */

const PORTAL_RX_PATIENT_ID = '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f';
const PORTAL_RX_OTHER_PATIENT_ID = '2d3e4f5a-6b7c-4d8e-9f0a-1b2c3d4e5f60';
const PORTAL_RX_APPOINTMENT_ID = '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34';
const PORTAL_RX_TRACKING_ID = '3e4f5a6b-7c8d-4e9f-8a1b-2c3d4e5f6a7b';

final class PortalRxCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

function portalRxAuthorization(PortalRxCallLog $log, bool $isPatient): AuthorizationServiceInterface
{
    return new class($log, $isPatient) implements AuthorizationServiceInterface
    {
        public function __construct(private PortalRxCallLog $log, private bool $isPatient) {}

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

            return PORTAL_RX_PATIENT_ID;
        }
    };
}

/**
 * @param  array{owner?: ?string, status?: string, tracking?: bool, recommendations?: ?string, prescriptions?: int, isPatient?: bool}  $world
 */
function portalRxUseCase(PortalRxCallLog $log, array $world = []): GetPatientPrescriptionsUseCase
{
    $owner = array_key_exists('owner', $world) ? $world['owner'] : PORTAL_RX_PATIENT_ID;
    $status = $world['status'] ?? 'completada';
    $hasTracking = $world['tracking'] ?? true;
    $recommendations = array_key_exists('recommendations', $world) ? $world['recommendations'] : 'Cepillarse tres veces al día';
    $prescriptions = $world['prescriptions'] ?? 2;

    $appointments = Mockery::mock(AppointmentsRepositoryInterface::class);
    $appointments->shouldReceive('findById')->andReturnUsing(function () use ($log, $owner, $status): ?AppointmentEntity {
        $log->calls[] = 'appointment';

        return $owner === null ? null : AppointmentEntity::fromPrimitives(
            id: PORTAL_RX_APPOINTMENT_ID,
            date: '2026-09-10',
            time: '10:15',
            whatsappReminder: false,
            status: $status,
            treatmentId: '1',
            userId: '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f',
            patientId: $owner,
            treatmentName: null,
            userFirstName: null,
            userLastName: null,
            patientName: null,
            treatmentTime: null,
            createdAt: '2026-09-01 09:00:00',
            updatedAt: '2026-09-01 09:00:00',
        );
    });

    $trackings = Mockery::mock(AppointmentTrackingRepositoryInterface::class);
    $trackings->shouldReceive('findByAppointmentId')->andReturnUsing(function () use ($log, $hasTracking, $recommendations): ?AppointmentTrackingEntity {
        $log->calls[] = 'tracking';

        return $hasTracking ? AppointmentTrackingEntity::fromPrimitives(
            id: PORTAL_RX_TRACKING_ID,
            appointmentId: PORTAL_RX_APPOINTMENT_ID,
            reason: 'Dolor de muela',
            symptoms: ['Dolor'],
            diagnosis: 'Caries',
            procedurePerformed: 'Resina',
            observations: 'Sin novedad',
            recommendations: $recommendations,
            createdAt: '2026-09-10 11:00:00',
            updatedAt: '2026-09-10 11:00:00',
        ) : null;
    });

    $rx = Mockery::mock(PrescriptionRepositoryInterface::class);
    $rx->shouldReceive('findAllByAppointmentTrackingId')->andReturnUsing(function () use ($log, $prescriptions): array {
        $log->calls[] = 'prescriptions';

        return array_map(fn (int $n): PrescriptionEntity => PrescriptionEntity::fromPrimitives(
            id: sprintf('4f5a6b7c-8d9e-4f0a-8b1c-2d3e4f5a6b%02d', $n),
            appointmentTrackingId: PORTAL_RX_TRACKING_ID,
            medication: 'Amoxicilina '.$n,
            dosage: '500 mg',
            durationDays: 7,
            dailyFrequency: 3,
            instructions: 'Tomar con alimentos',
            createdAt: '2026-09-10 11:00:00',
            updatedAt: '2026-09-10 11:00:00',
        ), $prescriptions > 0 ? range(1, $prescriptions) : []);
    });

    return new GetPatientPrescriptionsUseCase(
        portalRxAuthorization($log, $world['isPatient'] ?? true),
        new AppointmentsService($appointments),
        new AppointmentTrackingService($trackings),
        new PrescriptionService($rx),
    );
}

function portalRxDto(): GetAppointmentTrackingByAppointmentIdDTO
{
    return GetAppointmentTrackingByAppointmentIdDTO::create(PORTAL_RX_APPOINTMENT_ID);
}

it('gives the recommendations and prescriptions of a completed appointment of the patient', function () {
    $log = new PortalRxCallLog;

    $result = portalRxUseCase($log)->execute(portalRxDto());

    expect($result['recommendations'])->toBe('Cepillarse tres veces al día')
        ->and(array_map(fn (PrescriptionEntity $p): string => $p->Medication()->value, $result['prescriptions']))->toBe(['Amoxicilina 1', 'Amoxicilina 2'])
        ->and($log->calls)->toBe(['patient', 'appointment', 'tracking', 'prescriptions']);
});

it('treats an appointment of another patient as missing, whatever its status (abuse)', function (string $status) {
    $log = new PortalRxCallLog;

    expect(portalRxUseCase($log, ['owner' => PORTAL_RX_OTHER_PATIENT_ID, 'status' => $status])->execute(portalRxDto()))->toBeNull()
        ->and($log->calls)->toBe(['patient', 'appointment']);
})->with(['completada', 'asignada', 'cancelada']);

it('gives nothing for an appointment that does not exist', function () {
    expect(portalRxUseCase(new PortalRxCallLog, ['owner' => null])->execute(portalRxDto()))->toBeNull();
});

it('gives an empty answer for an open or cancelled appointment without reading its follow-up', function (string $status) {
    $log = new PortalRxCallLog;

    expect(portalRxUseCase($log, ['status' => $status])->execute(portalRxDto()))->toBe(['recommendations' => null, 'prescriptions' => []])
        ->and($log->calls)->toBe(['patient', 'appointment']);
})->with(['asignada', 'reprogramada', 'cancelada']);

it('gives an empty answer for a completed appointment without follow-up', function () {
    expect(portalRxUseCase(new PortalRxCallLog, ['tracking' => false])->execute(portalRxDto()))
        ->toBe(['recommendations' => null, 'prescriptions' => []]);
});

it('keeps the recommendations of a completed appointment without prescriptions', function () {
    $result = portalRxUseCase(new PortalRxCallLog, ['prescriptions' => 0])->execute(portalRxDto());

    expect($result)->toBe(['recommendations' => 'Cepillarse tres veces al día', 'prescriptions' => []]);
});

it('reads nothing when the actor is not an active patient (abuse)', function () {
    $log = new PortalRxCallLog;

    expect(fn () => portalRxUseCase($log, ['isPatient' => false])->execute(portalRxDto()))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe(['patient']);
});
