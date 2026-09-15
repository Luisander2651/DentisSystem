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

    /**
     * BR-21: an administrator may not deactivate, demote or delete their own account.
     * Nothing stopped it before, so a single-administrator clinic could lock itself out
     * of the admin panel irreversibly, with no way back through the interface.
     *
     * This is the identity rule, not the last-administrator rule: it does NOT stop
     * administrator A from deactivating administrator B, even when B was the last other
     * one. That wider guard was considered and deliberately left out of scope.
     */
    public static function selfLockout(string $action): self
    {
        return new self("You cannot {$action} your own administrator account.");
    }
}
