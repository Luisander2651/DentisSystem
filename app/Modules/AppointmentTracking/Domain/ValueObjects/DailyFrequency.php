<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects\DailyFrequencyException;

final readonly class DailyFrequency
{
    private function __construct(
        public int $value,
    ) {
        if ($value < 1 || $value > 24) {
            throw DailyFrequencyException::invalidFrequency(1, 24);
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
