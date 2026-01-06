<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;

final class UserRoleException extends ValueObjectsException
{
    public static function invalidFormat(UserRoleId $roleId): self
    {
        return new self("The user role ID {$roleId->value} has an invalid format.");
    }
    
    public static function notFound(UserRoleId $roleId): self
    {
        return new self("User role with ID {$roleId->value} not found.");
    }
}