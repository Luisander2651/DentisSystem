<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions;

use App\Modules\Patients\Domain\ValueObjects\Patients\PatientEmail;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientId;
use Exception;

final class PatientException extends Exception
{
    public static function shouldBeUniqueEmail(PatientEmail $email): self
    {
        return new self("The email {$email->value} is already in use by another patient.");
    }

    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof PatientId) {
            return new self("Patient with ID {$identifier->value} not found.");
        }

        return new self('Patient not found.');
    }

    public static function notFoundByRoleAndStatus(): self
    {
        return new self('No patients found with the provided filters.');
    }
}
