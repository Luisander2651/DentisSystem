<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Exceptions;

use Exception;

/**
 * Spec 017 (CA6): rescheduling and changing the status are separate actions. Its own class so the
 * controller answers it as an input error by its type, not by its text (/review R2).
 */
final class RescheduleAndStatusTogetherException extends Exception
{
    public static function create(): self
    {
        return new self('No puedes reprogramar y cambiar el estado de la cita a la vez.');
    }
}
