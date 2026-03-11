<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class MedicalDataIdException extends ValueObjectsException
{
    public static function mustBeGreaterThanZero(int $value): self
    {
        return new self("Medical data id must be greater than zero. Received: {$value}.");
    }
}

