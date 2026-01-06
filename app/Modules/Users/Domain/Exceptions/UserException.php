<?php

declare(strict_types=1);

namespace App\Modules\Users\Domain\Exceptions;

use App\Modules\Users\Domain\ValueObjects\UserEmail;
use App\Modules\Users\Domain\ValueObjects\UserId;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;

use Exception;

final class UserException extends Exception
{
    public static function shouldBeUniqueEmail(UserEmail $email): self
    {
        return new self("The email {$email->value} is already in use.");
    }

    public static function notFound($identifier): self
    {
        if ($identifier instanceof UserId) {
            return new self("User with ID {$identifier->value} not found.");
        }

        if ($identifier instanceof UserRoleId) {
            return new self("No users found with role ID {$identifier->value}.");
        }

        return new self("User not found.");
    }
}
