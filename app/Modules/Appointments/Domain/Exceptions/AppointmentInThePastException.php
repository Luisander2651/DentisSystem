<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Domain\Exceptions;

use Exception;

/**
 * Spec 017 (CA3, CA4): an appointment is never booked or moved to a moment equal to or earlier
 * than the clinic's "now". Its own class, and not an AppointmentException, because the API
 * answers it as an input error (422) and the rest of the appointment errors as conflicts (409).
 */
final class AppointmentInThePastException extends Exception
{
    public static function forReschedule(): self
    {
        return new self('No puedes reprogramar la cita a una fecha u hora que ya pasó.');
    }

    public static function forCreation(): self
    {
        return new self('No puedes agendar una cita en una fecha u hora que ya pasó.');
    }
}
