<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\AppointmentTracking\Application\DTOs\CompleteAppointmentDTO;
use App\Modules\AppointmentTracking\Application\UseCases\GetCompletedWithoutTrackingUseCase;
use App\Modules\AppointmentTracking\Application\UseCases\RegisterAppointmentTrackingUseCase;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentCompletionRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Repositories\PendingTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentCompletionService;
use App\Modules\AppointmentTracking\Domain\Service\AppointmentTrackingService;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 020 (CA13, CA14, TM1, TM3, A24): the use cases of the follow-up ask for permission before
 * anything else. Without appointment-tracking.create nor create-own - the assistant among them -
 * they refuse without reading; with it, the permission is asked first. The doctor only ever
 * reads their own completed appointments without follow-up.
 */

const TRACKING_AUTH_ACTOR_ID = '6f1c1d2e-3a4b-4c5d-8e9f-0a1b2c3d4e5f';

/**
 * What happened, in order: "can:<permission>" and "repository".
 */
final class TrackingCallLog
{
    /** @var list<string> */
    public array $calls = [];

    /** @var list<?string> */
    public array $scopes = [];
}

/**
 * @param  list<string>  $granted
 */
function trackingAuthorization(TrackingCallLog $log, array $granted): AuthorizationServiceInterface
{
    return new class($log, $granted) implements AuthorizationServiceInterface
    {
        /**
         * @param  list<string>  $granted
         */
        public function __construct(private TrackingCallLog $log, private array $granted) {}

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
            return TRACKING_AUTH_ACTOR_ID;
        }
    };
}

function trackingPendingRepository(TrackingCallLog $log): PendingTrackingRepositoryInterface
{
    $repository = Mockery::mock(PendingTrackingRepositoryInterface::class);
    $repository->shouldReceive('findCompletedWithoutTracking')->andReturnUsing(function (?string $userId) use ($log): array {
        $log->calls[] = 'repository';
        $log->scopes[] = $userId;

        return ['items' => [], 'total' => 0];
    });

    return $repository;
}

function trackingRegisterUseCase(TrackingCallLog $log, array $granted): RegisterAppointmentTrackingUseCase
{
    $logging = fn (string $method, mixed $answer) => function () use ($log, $answer): mixed {
        $log->calls[] = 'repository';

        return $answer;
    };

    $appointments = Mockery::mock(AppointmentsRepositoryInterface::class);
    $appointments->shouldReceive('findById')->andReturnUsing($logging('findById', null));
    $tracking = Mockery::mock(AppointmentTrackingRepositoryInterface::class);
    $tracking->shouldReceive('findByAppointmentId')->andReturnUsing($logging('findByAppointmentId', null));
    $completion = Mockery::mock(AppointmentCompletionRepositoryInterface::class);
    $completion->shouldReceive('register')->andReturnUsing($logging('register', null));

    return new RegisterAppointmentTrackingUseCase(
        trackingAuthorization($log, $granted),
        new AppointmentsService($appointments),
        new AppointmentTrackingService($tracking),
        new AppointmentCompletionService($completion),
    );
}

function trackingRegisterDto(): CompleteAppointmentDTO
{
    return CompleteAppointmentDTO::create(
        appointmentId: '0b5f6a9e-2c1d-4e3f-8a7b-9c0d1e2f3a4b',
        reason: 'Dolor en una muela',
        symptoms: ['dolor'],
        diagnosis: 'Caries',
        procedurePerformed: 'Resina',
        observations: null,
        recommendations: null,
        prescriptions: [],
    );
}

it('refuses the ones without follow-up to whoever may not register it, before reading (abuse)', function (array $granted) {
    $log = new TrackingCallLog;
    $useCase = new GetCompletedWithoutTrackingUseCase(trackingAuthorization($log, $granted), trackingPendingRepository($log));

    expect(fn () => $useCase->execute())->toThrow(AuthorizationException::class)
        ->and($log->calls)->not->toContain('repository')
        ->and($log->calls)->each->toStartWith('can:');
})->with([
    'sin permisos' => [[]],
    'asistente' => [['appointments.view', 'appointments.create', 'appointments.update', 'patients.view']],
]);

it('asks for its permission first and gives the doctor only their own (TM1)', function (array $granted, ?string $scope) {
    $log = new TrackingCallLog;
    $useCase = new GetCompletedWithoutTrackingUseCase(trackingAuthorization($log, $granted), trackingPendingRepository($log));

    $useCase->execute();

    expect($log->calls[0])->toStartWith('can:appointment-tracking.create')
        ->and(array_search('repository', $log->calls, true))->toBeGreaterThan(0)
        ->and($log->scopes)->toBe([$scope]);
})->with([
    'administrador' => [['appointment-tracking.create'], null],
    'doctor' => [['appointment-tracking.create-own'], TRACKING_AUTH_ACTOR_ID],
]);

it('refuses registering to whoever may not register it, before reading (abuse)', function (array $granted) {
    $log = new TrackingCallLog;
    $useCase = trackingRegisterUseCase($log, $granted);

    expect(fn () => $useCase->execute(trackingRegisterDto()))->toThrow(AuthorizationException::class)
        ->and($log->calls)->not->toContain('repository')
        ->and($log->calls)->each->toStartWith('can:');
})->with([
    'sin permisos' => [[]],
    'asistente' => [['appointments.view', 'appointments.create', 'appointments.update', 'patients.view']],
]);

it('asks for its permission first and only then reads the appointment', function (array $granted) {
    $log = new TrackingCallLog;
    $useCase = trackingRegisterUseCase($log, $granted);

    try {
        $useCase->execute(trackingRegisterDto());
    } catch (Throwable) {
        // The appointment does not exist here; only the order of the calls matters.
    }

    expect($log->calls[0])->toStartWith('can:appointment-tracking.create')
        ->and(array_search('repository', $log->calls, true))->toBeGreaterThan(0);
})->with([
    'administrador' => [['appointment-tracking.create']],
    'doctor' => [['appointment-tracking.create-own']],
]);
