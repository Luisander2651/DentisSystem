<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientName;

final class PatientNameException extends ValueObjectsException
{
    public static function invalidLength(PatientName $name, int $minLength, int $maxLength): self
    {
        return new self("The patient name length is invalid: {$name->full()}. Expected between {$minLength} and {$maxLength} characters.");
    }

    public static function invalidFormat(string $fullName): self
    {
        return new self("The patient name format is invalid: '{$fullName}'. Expected format: 'FirstName LastName'.");
    }
}

