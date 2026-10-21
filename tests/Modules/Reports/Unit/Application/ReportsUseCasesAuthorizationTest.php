<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Reports\Application\Contracts\MonthlyReportExporterInterface;
use App\Modules\Reports\Application\DTOs\MonthlyReportDTO;
use App\Modules\Reports\Application\UseCases\ExportMonthlyReportUseCase;
use App\Modules\Reports\Application\UseCases\GetMonthlyReportUseCase;
use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use App\Modules\Reports\Domain\Service\MonthlyReportService;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonInterface;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 019, CA10 and TM1: each use case asks for its permission before anything else. The routes
 * already stop every actor but the administrator (only.admin), so only here is it seen that the
 * use cases would stop them too.
 */

/**
 * What happened, in order: "can:<permission>", "repository" and "exporter".
 */
final class ReportsCallLog
{
    /** @var list<string> */
    public array $calls = [];
}

function reportsAuthorization(ReportsCallLog $log, array $granted): AuthorizationServiceInterface
{
    return new class($log, $granted) implements AuthorizationServiceInterface
    {
        public function __construct(private ReportsCallLog $log, private array $granted) {}

        public function assertCan(string $permission): void
        {
            $this->log->calls[] = 'can:'.$permission;

            if (! in_array($permission, $this->granted, true)) {
                throw AuthorizationException::forbidden($permission);
            }
        }

        public function can(string $permission): bool
        {
            return in_array($permission, $this->granted, true);
        }

        public function currentActorId(): string
        {
            return 'administrador';
        }
    };
}

function reportsRepositoryLogging(ReportsCallLog $log): MonthlyReportRepositoryInterface
{
    return new class($log) implements MonthlyReportRepositoryInterface
    {
        public function __construct(private ReportsCallLog $log) {}

        public function appointmentCounts(ReportMonth $month, CarbonInterface $today): AppointmentCounts
        {
            $this->log->calls[] = 'repository';

            return AppointmentCounts::empty();
        }

        public function newPatients(ReportMonth $month): int
        {
            $this->log->calls[] = 'repository';

            return 0;
        }

        public function activePatients(): int
        {
            $this->log->calls[] = 'repository';

            return 0;
        }

        public function treatmentRanking(ReportMonth $month): array
        {
            $this->log->calls[] = 'repository';

            return [];
        }

        public function staffLoad(ReportMonth $month): array
        {
            $this->log->calls[] = 'repository';

            return [];
        }

        public function demandByWeekdayAndHour(ReportMonth $month): array
        {
            $this->log->calls[] = 'repository';

            return [];
        }
    };
}

function reportsExporterLogging(ReportsCallLog $log): MonthlyReportExporterInterface
{
    return new class($log) implements MonthlyReportExporterInterface
    {
        public function __construct(private ReportsCallLog $log) {}

        public function export(MonthlyReport $report): string
        {
            $this->log->calls[] = 'exporter';

            return sys_get_temp_dir().'/reporte-de-prueba.xlsx';
        }
    };
}

it('refuses the report without reports.view before reading anything', function () {
    $log = new ReportsCallLog;
    $useCase = new GetMonthlyReportUseCase(new MonthlyReportService(reportsRepositoryLogging($log)), reportsAuthorization($log, ['reports.export']));

    expect(fn () => $useCase->execute(MonthlyReportDTO::create('2026-07')))->toThrow(AuthorizationException::class)
        ->and($log->calls)->toBe(['can:reports.view']);
});

it('refuses the download without reports.export before reading or writing anything', function () {
    $log = new ReportsCallLog;
    $useCase = new ExportMonthlyReportUseCase(new MonthlyReportService(reportsRepositoryLogging($log)), reportsExporterLogging($log), reportsAuthorization($log, ['reports.view']));

    expect(fn () => $useCase->execute(MonthlyReportDTO::create('2026-07')))->toThrow(AuthorizationException::class)
        ->and($log->calls)->toBe(['can:reports.export']);
})->skip('019: pendiente de T035 y T039');

it('asks for its permission first and then reads the report', function () {
    $log = new ReportsCallLog;
    $useCase = new GetMonthlyReportUseCase(new MonthlyReportService(reportsRepositoryLogging($log)), reportsAuthorization($log, ['reports.view']));

    $report = $useCase->execute(MonthlyReportDTO::create('2026-07'));

    expect($report)->toBeInstanceOf(MonthlyReport::class)
        ->and($log->calls[0])->toBe('can:reports.view')
        ->and(array_slice($log->calls, 1))->each->toBe('repository');
});

it('asks for its permission first, then reads and writes the file, and names it by month', function () {
    $log = new ReportsCallLog;
    $useCase = new ExportMonthlyReportUseCase(new MonthlyReportService(reportsRepositoryLogging($log)), reportsExporterLogging($log), reportsAuthorization($log, ['reports.export']));

    $file = $useCase->execute(MonthlyReportDTO::create('2026-07'));

    expect($log->calls[0])->toBe('can:reports.export')
        ->and(end($log->calls))->toBe('exporter')
        ->and($file)->toBe(['path' => sys_get_temp_dir().'/reporte-de-prueba.xlsx', 'filename' => 'reporte-dentissa-2026-07.xlsx']);
})->skip('019: pendiente de T035 y T039');
