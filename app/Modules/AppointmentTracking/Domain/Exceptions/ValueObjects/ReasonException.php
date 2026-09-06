<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjects;

use App\Modules\AppointmentTracking\Domain\Exceptions\ValueObjectsException;

final class ReasonException extends ValueObjectsException
{
    public static function emptyReason(): self
    {
        return new self('Reason must be a non-empty string.');
    }
}
