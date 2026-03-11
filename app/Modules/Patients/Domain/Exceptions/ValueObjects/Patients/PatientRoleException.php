<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientRole;

final class PatientRoleException extends ValueObjectsException
{
    public static function invalidRole(PatientRole $role): self
    {
        return new self("The patient role is invalid: {$role->value()}");
    }
}

