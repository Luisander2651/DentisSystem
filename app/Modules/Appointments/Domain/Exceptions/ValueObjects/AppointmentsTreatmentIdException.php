<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class AppointmentsTreatmentIdException extends ValueObjectsException
{
    public static function emptyValue(): self
    {
        return new self('Treatment ID cannot be empty.');
    }

    public static function invalidIntegerString(string $value): self
    {
        return new self(sprintf('Treatment ID must be a valid integer string. Given: <%s>.', $value));
    }

    public static function nonPositiveValue(string $value): self
    {
        return new self(sprintf('Treatment ID must be a positive integer. Given: <%s>.', $value));
    }
}
