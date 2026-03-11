<?php

declare(strict_types=1);

namespace App\Modules\Patients\Domain\Exceptions\ValueObjects\Patients;

use App\Modules\Patients\Domain\Exceptions\ValueObjectsException;

final class PasswordException extends ValueObjectsException
{
    public static function invalidFormat(): self
    {
        return new self('The password format is invalid.');
    }

    public static function failedToHash(): self
    {
        return new self('Failed to hash the password.');
    }
}

