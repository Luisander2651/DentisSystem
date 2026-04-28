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

    public static function notFoundByFilters(): self
    {
        return new self('No appointments found with the provided filters.');
    }

    public static function invalidStatusTransition(): self
    {
        return new self('Invalid status value. Allowed values are: completada, cancelada.');
    }

    public static function rescheduleRequiresDateOrTime(): self
    {
        return new self('At least one of date or time must be provided for rescheduling.');
    }
}
