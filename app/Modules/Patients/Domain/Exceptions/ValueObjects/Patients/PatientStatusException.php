<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;

final class PatientStatusException extends ValueObjectsException
{
    public static function invalidStatus(PatientStatus $status): self
    {
        return new self("The patient status is invalid: {$status->value()}");
    }
}

