<?php

declare(strict_types=1);

namespace App\Modules\Reports\Application\UseCases;

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Modules\Reports\Application\DTOs\MonthlyReportDTO;
use App\Modules\Reports\Domain\Exceptions\ReportMonthException;
use App\Modules\Reports\Domain\Service\MonthlyReportService;
use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;

/**
 * Spec 019 (CA1–CA7, CA9, CA10): the report of a month on screen, only with reports.view.
 */
final readonly class GetMonthlyReportUseCase
{
    public function __construct(
        private MonthlyReportService $service,
        private AuthorizationServiceInterface $authorization,
    ) {}

    /**
     * @throws ReportMonthException when the month stopped being one of the range
     */
    public function execute(MonthlyReportDTO $dto): MonthlyReport
    {
        $this->authorization->assertCan('reports.view');

        $today = now();

        return $this->service->build(ReportMonth::create($dto->month, $today), $today);
    }
}
