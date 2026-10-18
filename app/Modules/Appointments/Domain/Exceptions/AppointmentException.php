<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions;

use App\Modules\Appointments\Domain\ValueObjects\AppointmentId;
use Exception;

final class AppointmentException extends Exception
{
    public static function notFound(mixed $identifier): self
    {
        if ($identifier instanceof AppointmentId) {
            return new self("Appointment with ID {$identifier->value} not found.");
        }

        return new self('Appointment not found.');
    }

    public static function invalidStatusTransition(): self
    {
        return new self('Invalid status value. Allowed values are: completada, cancelada.');
    }

    /**
     * Spec 017 (CA8, CA11): a completed or cancelled appointment is closed; it can no longer be
     * moved or cancelled, and a cancelled one cannot be completed.
     */
    public static function cannotChangeClosedAppointment(): self
    {
        return new self('La cita ya está completada o cancelada y no se puede cambiar.');
    }
}
