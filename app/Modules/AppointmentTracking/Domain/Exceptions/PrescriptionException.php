<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions;

use App\Modules\AppointmentTracking\Domain\ValueObjects\PrescriptionId;
use Exception;

final class PrescriptionException extends Exception
{
    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof PrescriptionId) {
            return new self("Prescription with ID {$identifier->value} not found.");
        }

        return new self('Prescription not found.');
    }

    public static function alreadyExistsForAppointmentTracking(): self
    {
        return new self('Prescription already exists for this appointment tracking.');
    }
}
