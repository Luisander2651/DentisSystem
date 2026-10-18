<?php

declare(strict_types=1);

namespace App\Core\Authorization;

interface AuthorizationServiceInterface
{
    public function assertCan(string $permission): void;

    /**
     * Spec 018: whether the current actor holds the permission, without throwing; lets a use
     * case choose between a general permission and its "own" counterpart.
     */
    public function can(string $permission): bool;

    /**
     * Spec 018: the id of the active staff member acting, to compare it with the owner of a
     * resource. Throws when there is no staff actor.
     */
    public function currentActorId(): string;
}
