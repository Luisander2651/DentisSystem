<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\ValueObjects\UserName;
use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

final class UserNameException extends ValueObjectsException
{
    public static function invalidLength(UserName $name, int $minLength, int $maxLength): self
    {
        return new self("The username length is invalid: {$name->full()}. Expected between {$minLength} and {$maxLength} characters.");
    }

    public static function invalidFormat(string $fullName): self
    {
        return new self("The username format is invalid: '{$fullName}'. Expected format: 'FirstName LastName'.");
    }
}