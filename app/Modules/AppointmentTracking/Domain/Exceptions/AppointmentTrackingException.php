<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions;

use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use Exception;

final class AppointmentTrackingException extends Exception
{
    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof AppointmentTrackingId) {
            return new self("Appointment tracking with ID {$identifier->value} not found.");
        }

        return new self('Appointment tracking not found.');
    }

    public static function alreadyExistsForAppointment(string $appointmentId): self
    {
        return new self("An appointment tracking record already exists for appointment {$appointmentId}.");
    }
}
