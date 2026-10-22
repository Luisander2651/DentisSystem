<?php

declare(strict_types=1);

namespace App\Modules\Reports\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Reports\Application\Contracts\MonthlyReportExporterInterface;
use App\Modules\Reports\Application\DTOs\MonthlyReportDTO;
use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\Service\MonthlyReportService;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;

/**
 * Spec 019 (CA8, CA10): the report of a month as an Excel file, only with reports.export. It is
 * made from the same report as the screen, so both show the same figures (P13).
 */
final readonly class ExportMonthlyReportUseCase
{
    public function __construct(
        private MonthlyReportService $service,
        private MonthlyReportExporterInterface $exporter,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @return array{path: string, filename: string}
     *
     * @throws ReportMonthException when the month stopped being one of the range
     */
    public function execute(MonthlyReportDTO $dto): array
    {
        $this->authorization->assertCan('reports.export');

        $today = now();
        $month = ReportMonth::create($dto->month, $today);

        return [
            'path' => $this->exporter->export($this->service->build($month, $today)),
            'filename' => 'reporte-dentissa-'.$month->value().'.xlsx',
        ];
    }
}
