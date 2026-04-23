<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions\ValueObjects;

use App\Modules\Appointments\Domain\Exceptions\ValueObjectsException;

final class TreatmentDescriptionException extends ValueObjectsException
{
    public static function emptyDescription(): self
    {
        return new self('Treatment description must be a non-empty string.');
    }
}
