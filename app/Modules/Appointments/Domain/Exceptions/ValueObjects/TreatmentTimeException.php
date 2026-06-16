<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class TreatmentTimeException extends ValueObjectsException
{
    public static function invalidTime(int $minValue, int $maxValue): self
    {
        return new self(sprintf('Treatment time must be an integer between %d and %d.', $minValue, $maxValue));
    }
}
