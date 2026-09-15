<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Auth;

final class CurrentActorAuthorizationService implements AuthorizationServiceInterface
{
    public function assertCan(string $permission): void
    {
        $actor = Auth::user();

        if (! $actor instanceof UserModel) {
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
            'patients.delete',
            'patients.create',
            'appointments.view',
            'appointments.delete',
            'manage.certifications',
            'manage.gallery',
            'manage.promotions',
            'manage.testimonials',
            'treatments.view',
            'treatments.create',
            'treatments.update',
            'treatments.delete',
            'appointment-tracking.create',
            'appointment-tracking.view',
            'appointment-tracking.update',
            'appointment-tracking.prescriptions.create',
            'appointment-tracking.prescriptions.update',
            'appointment-tracking.prescriptions.delete',
        ];

        if (! in_array($permission, $adminPermissions, true)) {
            throw AuthorizationException::forbidden($permission);
        }

        // BR-16: same single source of truth as OnlyAdmin.
        $roleName = mb_strtolower((string) ($actor->role?->name ?? ''));

        if ($roleName !== mb_strtolower(UserRoleId::administrador()->value)) {
            throw AuthorizationException::forbidden($permission);
        }
    }
}
