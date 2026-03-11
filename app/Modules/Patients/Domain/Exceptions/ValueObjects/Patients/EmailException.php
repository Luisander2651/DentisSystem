<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;

final class EmailException extends ValueObjectsException
{
    public static function invalidFormat(PatientEmail $email): self
    {
        return new self("The email format is invalid: {$email->value}");
    }
}

