<?php

declare(strict_types=1);

namespace App\Modules\Reports\Application\DTOs;

final readonly class MonthlyReportDTO
{
    private function __construct(
        public string $month,
    ) {}

    /** The month as it was asked for, AAAA-MM; ReportMonth decides whether it is one of the range. */
    public static function create(string $month): self
    {
        return new self(month: $month);
    }
}
