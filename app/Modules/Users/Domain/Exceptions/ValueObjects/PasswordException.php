<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

final class PasswordException extends ValueObjectsException
{
    public static function invalidFormat(): self
    {
        return new self("The password format is invalid.");
    }

    public static function failedToHash(): self
    {
        return new self("Failed to hash the password.");
    }
}