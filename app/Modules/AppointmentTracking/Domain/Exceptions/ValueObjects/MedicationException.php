<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class MedicationException extends ValueObjectsException
{
    public static function emptyMedication(): self
    {
        return new self('Medication must be a non-empty string.');
    }
}
