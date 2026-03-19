<?php

declare(strict_types=1);

namespace App\Core\Authorization\Exceptions;

use RuntimeException;

final class AuthorizationException extends RuntimeException
{
    public static function unauthenticated(): self
    {
        return new self('Authentication is required.');
    }

    public static function forbidden(string $permission): self
    {
        return new self("You are not allowed to perform this action ({$permission}).");
    }

    public static function inactiveAccount(): self
    {
        return new self('Your account is inactive.');
    }
}
