<?php

declare(strict_types=1);

namespace App\Modules\Auth\Aplication\Exceptions;

use Exception;

final class AuthAplicationExceptions extends Exception
{
    public static function invalidActorType(string $actorType): self
    {
        return new self("Unsupported actor type: {$actorType}.");
    }

    public static function unauthenticatedContext(): self
    {
        return new self('No authenticated user context found.');
    }
}
