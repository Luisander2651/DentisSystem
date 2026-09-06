<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class DiagnosisException extends ValueObjectsException
{
    public static function emptyDiagnosis(): self
    {
        return new self('Diagnosis must be a non-empty string.');
    }
}
