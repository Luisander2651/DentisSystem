<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;

final class UserRoleException extends ValueObjectsException
{
    /**
     * BR-16: both factories take the offending literal as a plain string rather than a
     * UserRoleId. Building the value object in order to report that the value object
     * could not be built is the shape that made UserName::create() recurse forever
     * before BR-10, and with a readonly `$value` it would now fail on uninitialised
     * access instead.
     */
    public static function invalidFormat(string $role): self
    {
        return new self("The user role ID {$role} has an invalid format.");
    }

    public static function notFound(string $role): self
    {
        return new self("User role with ID {$role} not found.");
    }
}
