<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Service;

use App\Modules\Appointments\Aplication\Exceptions\AppointmentScheduleConflictException;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentDate;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentStatus;
use App\Modules\Appointments\Domain\ValueObjects\AppointmentTime;
use App\Modules\Appointments\Domain\ValueObjects\TreatmentId;

class ScheduleAvailabilityChecker
{
    public function __construct(
        private readonly AppointmentsService $appointmentsService,
        private readonly TreatmentsService $treatmentsService,
    ) {}

    /**
     * Pure interval-overlap check: two half-open intervals [startA, endA) and
     * [startB, endB) overlap iff each starts before the other ends.
     */
    public static function overlaps(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA < $endB && $endA > $startB;
    }

    public function ensureAvailable(
        AppointmentDate $date,
        AppointmentTime $time,
        TreatmentId $treatmentId,
        ?AppointmentId $excludeAppointmentId = null,
    ): void {
        $requestedStart = self::timeToMinutes($time->value);
        $treatmentDuration = $this->getTreatmentDurationMinutes($treatmentId);
        $requestedEnd = $requestedStart + $treatmentDuration;

        $appointments = $this->appointmentsService->findByStatusAndDate(
            status: null,
            date: $date,
        );

        foreach ($appointments as $appointment) {
            if ($appointment->Status()->equals(AppointmentStatus::cancelled())) {
                continue;
            }

            if ($excludeAppointmentId !== null && $appointment->Id()->equals($excludeAppointmentId)) {
                continue;
            }

            $existingStart = self::timeToMinutes($appointment->Time()->value);
            $existingDuration = (int) ($appointment->TreatmentTime()?->value ?? 0);

            if ($existingDuration <= 0) {
                continue;
            }

            $existingEnd = $existingStart + $existingDuration;

            if (self::overlaps($requestedStart, $requestedEnd, $existingStart, $existingEnd)) {
                throw AppointmentScheduleConflictException::occupied($date->value, $time->value);
            }
        }
    }

    private function getTreatmentDurationMinutes(TreatmentId $treatmentId): int
    {
        $treatment = $this->treatmentsService->findById($treatmentId);

        return (int) ($treatment->Time()->value ?? 0);
    }

    private static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hours * 60) + (int) $minutes;
    }
}
