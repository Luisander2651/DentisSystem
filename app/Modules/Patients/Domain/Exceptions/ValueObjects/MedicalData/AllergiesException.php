<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\MedicalData;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class AllergiesException extends ValueObjectsException
{
    public static function allValuesMustBeStrings(): self
    {
        return new self('All allergies values must be strings.');
    }
}

