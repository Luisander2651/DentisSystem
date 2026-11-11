<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Exceptions\AppointmentAplicationExceptions;
use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Aplication\UseCases\GetPatientAppointmentQrUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 025 (CA7, CA12, CA18, TM2, TM8): the QR code of the portal is drawn only for an open
 * appointment of the patient of the session. Someone else's appointment reads as missing,
 * whatever its status, so its existence never shows; a closed one of their own is refused.
 */

const PORTAL_QR_PATIENT_ID = '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f';
const PORTAL_QR_OTHER_PATIENT_ID = '2d3e4f5a-6b7c-4d8e-9f0a-1b2c3d4e5f60';
const PORTAL_QR_APPOINTMENT_ID = '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34';

final class PortalQrCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

function portalQrAuthorization(PortalQrCallLog $log, bool $isPatient): AuthorizationServiceInterface
{
    return new class($log, $isPatient) implements AuthorizationServiceInterface
    {
        public function __construct(private PortalQrCallLog $log, private bool $isPatient) {}

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

            return PORTAL_QR_PATIENT_ID;
        }
    };
}

function portalQrUseCase(PortalQrCallLog $log, ?string $owner, string $status = 'asignada', bool $isPatient = true): GetPatientAppointmentQrUseCase
{
    $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
    $repository->shouldReceive('findById')->andReturnUsing(function () use ($log, $owner, $status): ?AppointmentEntity {
        $log->calls[] = 'repository';

        return $owner === null ? null : AppointmentEntity::fromPrimitives(
            id: PORTAL_QR_APPOINTMENT_ID,
            date: '2026-09-22',
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

    $generator = new class($log) implements QrCodeGeneratorInterface
    {
        public function __construct(private PortalQrCallLog $log) {}

        public function svg(string $content): string
        {
            $this->log->calls[] = 'qr:'.$content;

            return '<svg>'.$content.'</svg>';
        }
    };

    return new GetPatientAppointmentQrUseCase(portalQrAuthorization($log, $isPatient), new AppointmentsService($repository), $generator);
}

it('asks for the patient first, then draws only the id of their open appointment', function (string $status) {
    $log = new PortalQrCallLog;

    $svg = portalQrUseCase($log, PORTAL_QR_PATIENT_ID, $status)->execute(GetAppointmentByIdDTO::create(PORTAL_QR_APPOINTMENT_ID));

    expect($svg)->toBe('<svg>'.PORTAL_QR_APPOINTMENT_ID.'</svg>')
        ->and($log->calls)->toBe(['patient', 'repository', 'qr:'.PORTAL_QR_APPOINTMENT_ID]);
})->with(['asignada', 'reprogramada']);

it('treats an appointment of another patient as missing, whatever its status (abuse)', function (string $status) {
    $log = new PortalQrCallLog;

    $svg = portalQrUseCase($log, PORTAL_QR_OTHER_PATIENT_ID, $status)->execute(GetAppointmentByIdDTO::create(PORTAL_QR_APPOINTMENT_ID));

    expect($svg)->toBeNull()
        ->and($log->calls)->toBe(['patient', 'repository']);
})->with(['asignada', 'completada', 'cancelada']);

it('gives nothing for an appointment that does not exist', function () {
    $log = new PortalQrCallLog;

    expect(portalQrUseCase($log, null)->execute(GetAppointmentByIdDTO::create(PORTAL_QR_APPOINTMENT_ID)))->toBeNull();
});

it('refuses the QR code of a closed appointment of their own without drawing it (abuse)', function (string $status) {
    $log = new PortalQrCallLog;

    expect(fn () => portalQrUseCase($log, PORTAL_QR_PATIENT_ID, $status)->execute(GetAppointmentByIdDTO::create(PORTAL_QR_APPOINTMENT_ID)))
        ->toThrow(AppointmentAplicationExceptions::class);

    expect($log->calls)->toBe(['patient', 'repository']);
})->with(['completada', 'cancelada']);

it('reads nothing when the actor is not an active patient (abuse)', function () {
    $log = new PortalQrCallLog;

    expect(fn () => portalQrUseCase($log, PORTAL_QR_PATIENT_ID, 'asignada', false)->execute(GetAppointmentByIdDTO::create(PORTAL_QR_APPOINTMENT_ID)))
        ->toThrow(AuthorizationException::class);

    expect($log->calls)->toBe(['patient']);
});
