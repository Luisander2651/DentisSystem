<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Exceptions;

use Exception;

final class AppointmentAplicationExceptions extends Exception
{
    public static function noInfoProvided(): self
    {
        return new self('At least one field must be provided for update.');
    }

    public static function rescheduleRequiresDateAndTime(): self
    {
        return new self('Necesitas proporcionar fecha y hora para reprogramar la cita.');
    }

    public static function rescheduleRequiresDateOrTime(): self
    {
        return new self('At least one of date or time must be provided for rescheduling.');
    }

    public static function invalidPatientId(): self
    {
        return new self('Patient ID cannot be empty');
    }
}
