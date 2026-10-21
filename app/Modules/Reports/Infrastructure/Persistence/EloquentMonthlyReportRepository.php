<?php

declare(strict_types=1);

namespace App\Modules\Reports\Infrastructure\Persistence;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Reports\Domain\Repositories\MonthlyReportRepositoryInterface;
use App\Modules\Reports\Domain\ValueObjects\AppointmentCounts;
use App\Modules\Reports\Domain\ValueObjects\RankedItem;
use App\Modules\Reports\Domain\ValueObjects\ReportMonth;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Spec 019 (ADR 0006): the counts of the monthly report, worked out in PostgreSQL with aggregate
 * queries over the models of Appointments, Patients and Users. It only reads, and it never
 * selects a column of a patient but to count it (CA12, TM3). An appointment belongs to the month
 * of its current date, so a rescheduled one counts where it is now (CA1).
 */
final class EloquentMonthlyReportRepository implements MonthlyReportRepositoryInterface
{
    public function appointmentCounts(ReportMonth $month, CarbonInterface $today): AppointmentCounts
    {
        $day = CarbonImmutable::instance($today)->toDateString();

        $counts = $this->appointmentsOf($month)
            ->toBase()
            ->selectRaw(
                "count(*) as total,
                count(*) filter (where status = 'completada') as completed,
                count(*) filter (where status = 'cancelada') as cancelled,
                count(*) filter (where status = 'reprogramada' and date >= ?) as rescheduled_pending,
                count(*) filter (where status = 'asignada' and date >= ?) as assigned_pending,
                count(*) filter (where status in ('asignada', 'reprogramada') and date < ?) as unclosed,
                count(*) filter (where status <> 'cancelada' and date < ?) as not_cancelled_past",
                [$day, $day, $day, $day],
            )
            ->first();

        return AppointmentCounts::create(
            total: (int) $counts->total,
            completed: (int) $counts->completed,
            cancelled: (int) $counts->cancelled,
            rescheduledPending: (int) $counts->rescheduled_pending,
            assignedPending: (int) $counts->assigned_pending,
            unclosed: (int) $counts->unclosed,
            notCancelledPast: (int) $counts->not_cancelled_past,
        );
    }

    public function newPatients(ReportMonth $month): int
    {
        return PatientModel::query()
            ->whereBetween('created_at', [$month->firstDay()->startOfDay()->toDateTimeString(), $month->lastDay()->endOfDay()->toDateTimeString()])
            ->count();
    }

    public function activePatients(): int
    {
        return PatientModel::query()->where('status', 'active')->count();
    }

    public function treatmentRanking(ReportMonth $month): array
    {
        $treatments = (new TreatmentModel)->getTable();

        return $this->notCancelledOf($month)
            ->join($treatments, "{$treatments}.id", '=', 'appointments.treatment_id')
            ->groupBy("{$treatments}.id", "{$treatments}.name")
            ->toBase()
            ->selectRaw("{$treatments}.name as name, count(*) as appointments")
            ->get()
            ->map(fn (object $row): RankedItem => RankedItem::create((string) $row->name, (int) $row->appointments))
            ->all();
    }

    public function staffLoad(ReportMonth $month): array
    {
        $users = (new UserModel)->getTable();

        return $this->notCancelledOf($month)
            ->join($users, "{$users}.id", '=', 'appointments.user_id')
            ->groupBy("{$users}.id", "{$users}.first_name", "{$users}.last_name")
            ->toBase()
            ->selectRaw("{$users}.first_name as first_name, {$users}.last_name as last_name, count(*) as appointments, count(*) filter (where appointments.status = 'completada') as completed")
            ->get()
            ->map(fn (object $row): RankedItem => RankedItem::create(trim($row->first_name.' '.$row->last_name), (int) $row->appointments, (int) $row->completed))
            ->all();
    }

    public function demandByWeekdayAndHour(ReportMonth $month): array
    {
        $demand = [];

        $rows = $this->notCancelledOf($month)
            ->toBase()
            ->selectRaw('extract(isodow from date)::int as weekday, extract(hour from time)::int as hour, count(*) as appointments')
            ->groupByRaw('1, 2')
            ->get();

        foreach ($rows as $row) {
            $demand[(int) $row->weekday][(int) $row->hour] = (int) $row->appointments;
        }

        return $demand;
    }

    /**
     * @return Builder<AppointmentModel>
     */
    private function appointmentsOf(ReportMonth $month): Builder
    {
        return AppointmentModel::query()->whereBetween('appointments.date', [$month->firstDay()->toDateString(), $month->lastDay()->toDateString()]);
    }

    /**
     * Cancelled appointments count only among the states: never in treatments, staff or the week.
     *
     * @return Builder<AppointmentModel>
     */
    private function notCancelledOf(ReportMonth $month): Builder
    {
        return $this->appointmentsOf($month)->where('appointments.status', '<>', 'cancelada');
    }
}
