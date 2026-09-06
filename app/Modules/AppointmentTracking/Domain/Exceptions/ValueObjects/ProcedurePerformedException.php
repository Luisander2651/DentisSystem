<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class ProcedurePerformedException extends ValueObjectsException
{
    public static function emptyProcedurePerformed(): self
    {
        return new self('Procedure performed must be a non-empty string.');
    }
}
