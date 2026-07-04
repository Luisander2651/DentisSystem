<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

final class PasswordResetServiceException extends Exception
{
    public static function TokenNotFound(): self
    {
        return new self('The provided reset token was not found or has expired.');
    }

    public static function TokenAlreadyUsed(): self
    {
        return new self('The provided reset token has already been used or is invalid.');
    }

    public static function UserNotFound(): self
    {
        return new self('The user associated with the provided email was not found.');
    }
}