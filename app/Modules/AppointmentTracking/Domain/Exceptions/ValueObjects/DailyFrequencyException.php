<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class DailyFrequencyException extends ValueObjectsException
{
    public static function invalidFrequency(int $minValue, int $maxValue): self
    {
        return new self(sprintf('Daily frequency must be an integer between %d and %d.', $minValue, $maxValue));
    }
}
