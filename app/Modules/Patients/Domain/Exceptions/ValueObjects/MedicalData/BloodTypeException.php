<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class BloodTypeException extends ValueObjectsException
{
    public static function invalidType(string $value): self
    {
        return new self("Invalid blood type: {$value}.");
    }
}

