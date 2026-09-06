<?php

declare(strict_types=1);

namespace App\Modules\AppointmentTracking\Application\Exceptions;

use Exception;

final class PrescriptionApplicationException extends Exception
{
    public static function noInfoProvided(): self
    {
        return new self('At least one field must be provided for update.');
    }
}
