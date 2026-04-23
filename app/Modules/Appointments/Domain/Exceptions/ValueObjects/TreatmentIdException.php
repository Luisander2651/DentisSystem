<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class TreatmentIdException extends ValueObjectsException
{
    public static function invalidValue(string $value): self
    {
        return new self(sprintf('Invalid treatment id value: <%s>. It must be a positive integer.', $value));
    }
}
