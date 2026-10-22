<?php

declare(strict_types=1);

namespace App\Modules\Reports\Application\Contracts;

use App\Modules\Reports\Domain\ValueObjects\MonthlyReport;

/**
 * Spec 019 (CA8, ADR 0005): writes the report of a month as a spreadsheet in a temporary file and
 * gives its path. The file is sent once and then deleted (D10).
 */
interface MonthlyReportExporterInterface
{
    public function export(MonthlyReport $report): string;
}
