<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class DurationDaysException extends ValueObjectsException
{
    public static function invalidDuration(int $minValue, int $maxValue): self
    {
        return new self(sprintf('Duration days must be an integer between %d and %d.', $minValue, $maxValue));
    }
}
