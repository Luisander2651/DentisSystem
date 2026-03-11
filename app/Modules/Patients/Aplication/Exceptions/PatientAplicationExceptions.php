<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\Exceptions;

use Exception;

final class PatientAplicationExceptions extends Exception
{
    public static function NoInfoRetrivered(): self
    {
        return new self('At least one field must be provided for update.');
    }
}
