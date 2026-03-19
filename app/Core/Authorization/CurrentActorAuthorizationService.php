<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Auth;

final class CurrentActorAuthorizationService implements AuthorizationServiceInterface
{
    public function assertCan(string $permission): void
    {
        $actor = Auth::user();

        if (!$actor instanceof UserModel) {
            throw AuthorizationException::unauthenticated();
        }

        if (($actor->status ?? null) !== 'active') {
            throw AuthorizationException::inactiveAccount();
        }

        $adminPermissions = [
            'users.create',
            'users.update',
            'users.delete',
            'users.manage',
            'users.view',
        ];

        if (!in_array($permission, $adminPermissions, true)) {
            throw AuthorizationException::forbidden($permission);
        }

        $roleName = strtolower((string) ($actor->role?->name ?? ''));

        if ($roleName !== 'admin') {
            throw AuthorizationException::forbidden($permission);
        }
    }
}
