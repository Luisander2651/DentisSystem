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

    public static function invalidPatientId(): self
    {
        return new self('Patient ID cannot be empty');
    }

    /**
     * Spec 025 (CA18, TM8): a completed or cancelled appointment is no longer a pass to the
     * clinic, so the portal draws no QR code for it.
     */
    public static function qrOnlyForOpenAppointments(): self
    {
        return new self('The QR code is only available for open appointments.');
    }
}
