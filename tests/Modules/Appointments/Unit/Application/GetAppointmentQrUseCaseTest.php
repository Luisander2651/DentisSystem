<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentByIdDTO;
use App\Modules\Appointments\Aplication\Support\QrCodeGeneratorInterface;
use App\Modules\Appointments\Aplication\UseCases\GetAppointmentQrUseCase;
use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 024 (CA3, CA4, CA10, TM1): the QR code of an appointment asks for permission before
 * anything else, draws only the id of an appointment that exists and draws nothing otherwise.
 */

const QR_USE_CASE_APPOINTMENT_ID = '9b2e6c1a-4f1d-4c3e-8a7b-2d5f0e9c1a34';

/**
 * What happened, in order: "can:<permission>", "repository" and "qr:<content>".
 */
final class AppointmentQrCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

/**
 * @param  list<string>  $granted
 */
function appointmentQrAuthorization(AppointmentQrCallLog $log, array $granted): AuthorizationServiceInterface
{
    return new class($log, $granted) implements AuthorizationServiceInterface
    {
        /**
         * @param  list<string>  $granted
         */
        public function __construct(private AppointmentQrCallLog $log, private array $granted) {}

        public function assertCan(string $permission): void
        {
            $this->log->calls[] = 'can:'.$permission;

            if (! in_array($permission, $this->granted, true)) {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            $this->log->calls[] = 'can:'.$permission;

            return in_array($permission, $this->granted, true);
        }

        public function currentActorId(): string
        {
            return '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f';
        }
    };
}

function appointmentQrRepository(AppointmentQrCallLog $log, bool $exists): AppointmentsRepositoryInterface
{
    $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
    $repository->shouldReceive('findById')->andReturnUsing(function () use ($log, $exists): ?AppointmentEntity {
        $log->calls[] = 'repository';

        return $exists ? AppointmentEntity::fromPrimitives(
            id: QR_USE_CASE_APPOINTMENT_ID,
            date: '2026-09-22',
            time: '10:15',
            whatsappReminder: false,
            status: 'asignada',
            treatmentId: '1',
            userId: '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f',
            patientId: '1c2d3e4f-5a6b-4c7d-8e9f-0a1b2c3d4e5f',
            treatmentName: null,
            userFirstName: null,
            userLastName: null,
            patientName: null,
            treatmentTime: null,
            createdAt: '2026-09-01 09:00:00',
            updatedAt: '2026-09-01 09:00:00',
        ) : null;
    });

    return $repository;
}

function appointmentQrGenerator(AppointmentQrCallLog $log): QrCodeGeneratorInterface
{
    return new class($log) implements QrCodeGeneratorInterface
    {
        public function __construct(private AppointmentQrCallLog $log) {}

        public function svg(string $content): string
        {
            $this->log->calls[] = 'qr:'.$content;

            return '<svg>'.$content.'</svg>';
        }
    };
}

/**
 * @param  list<string>  $granted
 */
function appointmentQrUseCase(AppointmentQrCallLog $log, array $granted, bool $exists = true): GetAppointmentQrUseCase
{
    return new GetAppointmentQrUseCase(
        appointmentQrAuthorization($log, $granted),
        new AppointmentsService(appointmentQrRepository($log, $exists)),
        appointmentQrGenerator($log),
    );
}

it('asks for permission first, then reads the appointment and draws only its id', function () {
    $log = new AppointmentQrCallLog;

    $svg = appointmentQrUseCase($log, ['appointments.view-detail'])
        ->execute(GetAppointmentByIdDTO::create(QR_USE_CASE_APPOINTMENT_ID));

    expect($svg)->toBe('<svg>'.QR_USE_CASE_APPOINTMENT_ID.'</svg>')
        ->and($log->calls)->toBe(['can:appointments.view-detail', 'repository', 'qr:'.QR_USE_CASE_APPOINTMENT_ID]);
})->skip('024: pendiente de T021');

it('refuses whoever may not see the detail before reading or drawing anything (abuse)', function (array $granted) {
    $log = new AppointmentQrCallLog;
    $useCase = appointmentQrUseCase($log, $granted);

    expect(fn () => $useCase->execute(GetAppointmentByIdDTO::create(QR_USE_CASE_APPOINTMENT_ID)))
        ->toThrow(AuthorizationException::class)
        ->and($log->calls)->toBe(['can:appointments.view-detail']);
})->with([
    'sin permisos' => [[]],
    'otros permisos' => [['appointments.view', 'patients.view']],
])->skip('024: pendiente de T021');

it('draws nothing for an appointment that does not exist', function () {
    $log = new AppointmentQrCallLog;

    $svg = appointmentQrUseCase($log, ['appointments.view-detail'], exists: false)
        ->execute(GetAppointmentByIdDTO::create(QR_USE_CASE_APPOINTMENT_ID));

    expect($svg)->toBeNull()
        ->and($log->calls)->toBe(['can:appointments.view-detail', 'repository']);
})->skip('024: pendiente de T021');

it('rejects an id that is not a UUID without reading nor drawing', function () {
    $log = new AppointmentQrCallLog;
    $useCase = appointmentQrUseCase($log, ['appointments.view-detail']);

    expect(fn () => $useCase->execute(GetAppointmentByIdDTO::create('no-es-uuid')))
        ->toThrow(InvalidArgumentException::class)
        ->and($log->calls)->toBe(['can:appointments.view-detail']);
})->skip('024: pendiente de T021');
