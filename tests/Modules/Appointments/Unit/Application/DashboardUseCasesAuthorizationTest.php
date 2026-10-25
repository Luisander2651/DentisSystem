<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Appointments\Aplication\UseCases\GetTodaySummaryUseCase;
use App\Modules\Appointments\Aplication\UseCases\GetUnclosedAppointmentsUseCase;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use App\Modules\Appointments\Domain\Service\AppointmentsService;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentPage;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 020 (CA13, CA14, TM1): the use cases of the dashboard ask for permission before anything
 * else. Without appointments.view nor appointments.view-own they refuse without reading; with
 * it, the permission is asked first and only then is the repository read.
 */

/**
 * What happened, in order: "can:<permission>" and "repository".
 */
final class DashboardCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

/**
 * @param  list<string>  $granted
 */
function dashboardAuthorization(DashboardCallLog $log, array $granted): AuthorizationServiceInterface
{
    return new class($log, $granted) implements AuthorizationServiceInterface
    {
        /**
         * @param  list<string>  $granted
         */
        public function __construct(private DashboardCallLog $log, private array $granted) {}

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

function dashboardRepositoryLogging(DashboardCallLog $log): AppointmentsRepositoryInterface
{
    $repository = Mockery::mock(AppointmentsRepositoryInterface::class);
    $repository->shouldReceive('findAllByStatusAndDateOrPatientId')->andReturnUsing(function () use ($log): array {
        $log->calls[] = 'repository';

        return [];
    });
    $repository->shouldReceive('findUnclosedBefore')->andReturnUsing(function () use ($log): AppointmentPage {
        $log->calls[] = 'repository';

        return AppointmentPage::create([], 0);
    });

    return $repository;
}

/**
 * @return array<string, Closure(AppointmentsService, AuthorizationServiceInterface): object>
 */
function dashboardUseCases(): array
{
    return [
        'resumen de hoy' => fn (AppointmentsService $service, AuthorizationServiceInterface $authorization): object => new GetTodaySummaryUseCase($service, $authorization),
        'sin cerrar' => fn (AppointmentsService $service, AuthorizationServiceInterface $authorization): object => new GetUnclosedAppointmentsUseCase($service, $authorization),
    ];
}

it('refuses whoever may not see appointments before reading anything (abuse)', function (string $useCase, array $granted) {
    $log = new DashboardCallLog;
    $build = dashboardUseCases()[$useCase];
    $instance = $build(new AppointmentsService(dashboardRepositoryLogging($log)), dashboardAuthorization($log, $granted));

    expect(fn () => $instance->execute())->toThrow(AuthorizationException::class)
        ->and($log->calls)->not->toContain('repository')
        ->and($log->calls)->each->toStartWith('can:');
})->with(['resumen de hoy', 'sin cerrar'])->with([
    'sin permisos' => [[]],
    'otros permisos' => [['patients.view', 'reports.view']],
])->skip('020: pendiente de T033');

it('asks for its permission first and then reads the appointments', function (string $useCase, array $granted) {
    $log = new DashboardCallLog;
    $build = dashboardUseCases()[$useCase];
    $instance = $build(new AppointmentsService(dashboardRepositoryLogging($log)), dashboardAuthorization($log, $granted));

    $instance->execute();

    expect($log->calls[0])->toStartWith('can:appointments.view')
        ->and($log->calls)->toContain('repository')
        ->and(array_search('repository', $log->calls, true))->toBeGreaterThan(0);
})->with(['resumen de hoy', 'sin cerrar'])->with([
    'administrador o asistente' => [['appointments.view']],
    'doctor' => [['appointments.view-own']],
])->skip('020: pendiente de T033');
