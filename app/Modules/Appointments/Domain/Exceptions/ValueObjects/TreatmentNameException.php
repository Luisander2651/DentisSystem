<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class TreatmentNameException extends ValueObjectsException
{
    public static function invalidLength(int $maxLength): self
    {
        return new self(sprintf('Treatment name must be a non-empty string up to %d characters.', $maxLength));
    }
}
