<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Entities\AppointmentEntity;
use DateTimeImmutable;

/**
 * Spec 020 (CA1, CA2, CA3, D25): the appointments of today ordered by time, with the counters of
 * the day and the next one to attend. The next is the first assigned or rescheduled appointment
 * by time, even when its time has passed: it only stops being the next once it is completed or
 * cancelled. After it, the two following ones to attend and how many more remain.
 */
final readonly class TodayAgenda
{
    private const AFTER_LIMIT = 2;

    /**
     * @param  list<AppointmentEntity>  $appointments
     * @param  list<AppointmentEntity>  $after
     */
    private function __construct(
        public array $appointments,
        public int $total,
        public int $completed,
        public int $pending,
        public int $cancelled,
        public ?AppointmentEntity $next,
        public array $after,
        public int $afterMore,
        private string $now,
    ) {}

    /**
     * @param  list<AppointmentEntity>  $appointments
     */
    public static function fromAppointments(array $appointments, DateTimeImmutable $now): self
    {
        usort($appointments, fn (AppointmentEntity $a, AppointmentEntity $b): int => strcmp(self::minutesOf($a), self::minutesOf($b)));

        $toAttend = array_values(array_filter($appointments, self::isToAttend(...)));
        $completed = count(array_filter($appointments, fn (AppointmentEntity $appointment): bool => $appointment->Status()->isCompleted()));
        $cancelled = count(array_filter($appointments, fn (AppointmentEntity $appointment): bool => $appointment->Status()->isCancelled()));
        $after = array_slice($toAttend, 1, self::AFTER_LIMIT);

        return new self(
            appointments: $appointments,
            total: count($appointments),
            completed: $completed,
            pending: count($toAttend),
            cancelled: $cancelled,
            next: $toAttend[0] ?? null,
            after: $after,
            afterMore: max(0, count($toAttend) - 1 - count($after)),
            now: $now->format('H:i'),
        );
    }

    /**
     * The contract's `is_past`: its time is earlier than now.
     */
    public function isPast(AppointmentEntity $appointment): bool
    {
        return strcmp(self::minutesOf($appointment), $this->now) < 0;
    }

    public function isNext(AppointmentEntity $appointment): bool
    {
        return $this->next !== null && $this->next->Id()->value === $appointment->Id()->value;
    }

    private static function isToAttend(AppointmentEntity $appointment): bool
    {
        return $appointment->Status()->isAssigned() || $appointment->Status()->isRescheduled();
    }

    /**
     * "H:i" of the appointment, whether it is stored as "H:i" or "H:i:s".
     */
    private static function minutesOf(AppointmentEntity $appointment): string
    {
        return substr($appointment->Time()->value, 0, 5);
    }
}
