<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\DurationDaysException;

final readonly class DurationDays
{
    private function __construct(
        public int $value,
    ) {
        if ($value < 1 || $value > 365) {
            throw DurationDaysException::invalidDuration(1, 365);
        }
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
