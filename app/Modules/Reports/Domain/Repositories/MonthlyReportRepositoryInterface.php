<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\Repositories;

use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use Carbon\CarbonInterface;

/**
 * Spec 019 (ADR 0006): what the monthly report reads, already counted. It answers aggregates
 * only - counts, names of treatments and names of the staff - and never a patient.
 */
interface MonthlyReportRepositoryInterface
{
    /** The appointments of the month by state; "unclosed" and "pending" are split by today (D7). */
    public function appointmentCounts(ReportMonth $month, CarbonInterface $today): AppointmentCounts;

    /** Patients registered in the month. */
    public function newPatients(ReportMonth $month): int;

    /** Patients whose file is active now. */
    public function activePatients(): int;

    /**
     * Treatments with their not cancelled appointments of the month, those with none left out.
     *
     * @return list<RankedItem>
     */
    public function treatmentRanking(ReportMonth $month): array;

    /**
     * Members of the staff with not cancelled appointments in the month, active or not, with the
     * completed ones as the extra figure.
     *
     * @return list<RankedItem>
     */
    public function staffLoad(ReportMonth $month): array;

    /**
     * Not cancelled appointments of the month by ISO weekday (1 = Monday) and hour (0–23); only
     * the pairs with appointments.
     *
     * @return array<int, array<int, int>>
     */
    public function demandByWeekdayAndHour(ReportMonth $month): array;
}
