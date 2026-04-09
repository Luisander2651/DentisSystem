<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\Exceptions\PatientRecord;

use Exception;

final class PatientRecordAplicationExceptions extends Exception
{
    public static function IdNotProvided(): self
    {
        return new self('Patient id is required.');
    }

    public static function NotFound(): self
    {
        return new self('Patient record not found.');
    }
}
