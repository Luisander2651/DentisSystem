<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class SymptomsException extends ValueObjectsException
{
    public static function allValuesMustBeNonEmptyStrings(): self
    {
        return new self('All symptoms values must be non-empty strings.');
    }
}
