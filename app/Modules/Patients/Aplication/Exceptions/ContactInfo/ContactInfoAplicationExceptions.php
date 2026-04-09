<?php

declare(strict_types=1);

namespace App\Modules\Patients\Aplication\Exceptions\ContactInfo;

use Exception;

final class ContactInfoAplicationExceptions extends Exception
{
    public static function AlreadyExists(): self
    {
        return new self('Contact info already exists for this patient.');
    }

    public static function NoInfoProvided(): self
    {
        return new self('At least one field must be provided for update.');
    }

    public static function IdNotProvided(): self
    {
        return new self('Patient id is required.');
    }

    public static function NotFound(): self
    {
        return new self('Contact info not found.');
    }
}
