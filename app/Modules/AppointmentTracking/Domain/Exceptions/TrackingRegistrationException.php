<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Domain\Exceptions;

use Exception;

/**
 * Spec 020 (CA15, A9, TM5): why a follow-up cannot be registered or an appointment completed, in
 * the Spanish of the contract and without any data of the appointment.
 */
final class TrackingRegistrationException extends Exception
{
    public static function alreadyRegistered(): self
    {
        return new self('Esta cita ya tiene seguimiento registrado.');
    }

    public static function notCompleted(): self
    {
        return new self('Solo se registra el seguimiento de una cita completada.');
    }

    public static function cancelledAppointment(): self
    {
        return new self('No se puede completar una cita cancelada.');
    }

    public static function appointmentNotFound(): self
    {
        return new self('No se encontró la cita.');
    }
}
