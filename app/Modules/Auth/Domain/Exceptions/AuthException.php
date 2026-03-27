<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Exceptions;

use Exception;

final class AuthException extends Exception
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid credentials.');
    }

    public static function inactiveAccount(string $actorType): self
    {
        return new self("The {$actorType} account is inactive.");
    }

    public static function emailAlreadyInUse(string $email): self
    {
        return new self("The email {$email} is already in use.");
    }

    public static function tokenRevocationFailed(): self
    {
        return new self('Unable to revoke the current token.');
    }

    public static function passwordsDoNotMatch(): self
    {
        return new self('The provided password and confirmation do not match.');
    }
}
