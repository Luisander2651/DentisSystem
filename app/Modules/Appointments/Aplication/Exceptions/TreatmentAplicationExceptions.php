<?php

declare(strict_types=1);

namespace App\Modules\Appointments\Aplication\Exceptions;

use Exception;

final class TreatmentAplicationExceptions extends Exception
{
    public static function noInfoProvided(): self
    {
        return new self('At least one field must be provided for update.');
    }
}