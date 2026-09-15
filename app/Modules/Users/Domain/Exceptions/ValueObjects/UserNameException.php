<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

final class UserNameException extends ValueObjectsException
{
    /**
     * BR-10: this used to take a UserName, which forced UserName::create() to build one
     * by calling ITSELF with the arguments that had just failed validation - an infinite
     * recursion with no base case that ended in a PHP Fatal error, uncatchable by any
     * try/catch. It now takes the already-formatted name as a plain string, so reporting
     * an invalid name can never re-enter validation.
     */
    public static function invalidLength(string $fullName, int $minLength, int $maxLength): self
    {
        return new self("The username length is invalid: {$fullName}. Expected between {$minLength} and {$maxLength} characters.");
    }

    public static function invalidFormat(string $fullName): self
    {
        return new self("The username format is invalid: '{$fullName}'. Expected format: 'FirstName LastName'.");
    }
}
