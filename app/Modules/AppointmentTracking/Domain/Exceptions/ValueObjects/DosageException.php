<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class DosageException extends ValueObjectsException
{
    public static function emptyDosage(): self
    {
        return new self('Dosage must be a non-empty string.');
    }
}
