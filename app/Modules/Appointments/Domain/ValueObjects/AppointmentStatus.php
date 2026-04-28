<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjects\AppointmentStatusException;

final readonly class AppointmentStatus
{
    private const completed = 'completada';
    private const cancelled = 'cancelada';
    private const rescheduled = 'reprogramada';
    private const assigned = 'asignada';
    private const VALID_STATUSES = [
        self::assigned,
        self::completed,
        self::cancelled,
        self::rescheduled,
    ];
    public function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw AppointmentStatusException::invalidStatus($value, self::VALID_STATUSES);
        }
    }

    public static function assigned(): self
    {
        return new self(self::assigned);
    }

    public static function completed(): self
    {
        return new self(self::completed);
    }

    public static function cancelled(): self
    {
        return new self(self::cancelled);
    }

    public static function rescheduled(): self
    {
        return new self(self::rescheduled);
    }

    public static function fromString(string $status): self
    {
        return new self($status);
    }

    public function isAssigned(): bool
    {
        return $this->value === self::assigned;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::completed;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::cancelled;
    }

    public function isRescheduled(): bool
    {
        return $this->value === self::rescheduled;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
