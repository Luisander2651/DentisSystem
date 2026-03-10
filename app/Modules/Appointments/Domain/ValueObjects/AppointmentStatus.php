<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class AppointmentStatus
{
    private const VALID_STATUSES = ['asignada', 'completada', 'cancelada', 'reprogramada'];

    public function __construct(
        public string $value,
    ) {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    "<%s> does not allow the value <%s>. Valid values: %s",
                    static::class,
                    $value,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }
    }

    public static function assigned(): self
    {
        return new self('asignada');
    }

    public static function completed(): self
    {
        return new self('completada');
    }

    public static function cancelled(): self
    {
        return new self('cancelada');
    }

    public static function rescheduled(): self
    {
        return new self('reprogramada');
    }

    public static function fromString(string $status): self
    {
        return new self($status);
    }

    public function isAssigned(): bool
    {
        return $this->value === 'asignada';
    }

    public function isCompleted(): bool
    {
        return $this->value === 'completada';
    }

    public function isCancelled(): bool
    {
        return $this->value === 'cancelada';
    }

    public function isRescheduled(): bool
    {
        return $this->value === 'reprogramada';
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
