<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class AppointmentDateException extends ValueObjectsException
{
    public static function invalidFormat(string $value, string $format): self
    {
        return new self(sprintf('Invalid appointment date <%s>. Expected format: %s', $value, $format));
    }
}
