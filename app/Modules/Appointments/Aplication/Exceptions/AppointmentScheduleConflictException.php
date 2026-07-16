<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Exceptions;

use Exception;

final class AppointmentScheduleConflictException extends Exception
{
    public static function occupied(string $date, string $time): self
    {
        return new self("La cita para la fecha {$date} y hora {$time} ya está ocupada.");
    }
}