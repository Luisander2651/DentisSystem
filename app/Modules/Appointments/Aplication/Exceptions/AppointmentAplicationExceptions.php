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

    /** Spec 017 (CA6): rescheduling and changing the status are separate actions. */
    public static function rescheduleAndStatusTogether(): self
    {
        return new self('No puedes reprogramar y cambiar el estado de la cita a la vez.');
    }

    public static function invalidPatientId(): self
    {
        return new self('Patient ID cannot be empty');
    }
}
