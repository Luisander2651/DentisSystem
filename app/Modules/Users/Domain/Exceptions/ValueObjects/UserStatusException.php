<?php 

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions\ValueObjects;

use App\Modules\Users\Domain\Exceptions\ValueObjectsException;
use App\Modules\Users\Domain\ValueObjects\UserStatus;

final class UserStatusException extends ValueObjectsException
{
    public static function invalidStatus(UserStatus $status): self
    {
        return new self("The user status '{$status->value}' is invalid.");
    }
}