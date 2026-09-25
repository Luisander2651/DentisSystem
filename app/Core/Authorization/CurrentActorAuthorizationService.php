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

        $allowedRoles = $this->permissions()[$permission] ?? null;

        if ($allowedRoles === null) {
            throw AuthorizationException::forbidden($permission);
        }

        // BR-16: same single source of truth as OnlyAdmin.
        $roleName = mb_strtolower((string) ($actor->role?->name ?? ''));

        if (! in_array($roleName, $allowedRoles, true)) {
            throw AuthorizationException::forbidden($permission);
        }
    }

    /**
     * Spec 014: the single source of truth for the "Permisos esperados" table. Each permission
     * lists the staff roles that hold it; anything not listed is denied by default.
     *
     * @return array<string, list<string>>
     */
    private function permissions(): array
    {
        $administrador = mb_strtolower(UserRoleId::administrador()->value);
        $asistente = mb_strtolower(UserRoleId::asistente()->value);
        $doctor = mb_strtolower(UserRoleId::doctor()->value);

        $allStaff = [$administrador, $asistente, $doctor];
        $clinicalStaff = [$administrador, $asistente];
        $adminOnly = [$administrador];

        return [
            'patients.view' => $allStaff,
            'patients.record.view' => $allStaff,
            'appointments.patient-history.view' => $allStaff,
            'appointments.view-detail' => $allStaff,
            'patients.clinical-data.manage' => $clinicalStaff,
            'patients.update' => $adminOnly,
            'patients.create' => $adminOnly,
            'patients.delete' => $adminOnly,
            'appointments.view' => $adminOnly,
            'appointments.create' => $adminOnly,
            'appointments.update' => $adminOnly,
            'appointments.delete' => $adminOnly,
            'agenda.selectors.view' => $adminOnly,
            'users.create' => $adminOnly,
            'users.update' => $adminOnly,
            'users.delete' => $adminOnly,
            'users.manage' => $adminOnly,
            'users.view' => $adminOnly,
            'manage.certifications' => $adminOnly,
            'manage.gallery' => $adminOnly,
            'manage.promotions' => $adminOnly,
            'manage.testimonials' => $adminOnly,
            'treatments.view' => $adminOnly,
            'treatments.create' => $adminOnly,
            'treatments.update' => $adminOnly,
            'treatments.delete' => $adminOnly,
            'appointment-tracking.create' => $adminOnly,
            'appointment-tracking.view' => $adminOnly,
            'appointment-tracking.update' => $adminOnly,
            'appointment-tracking.prescriptions.create' => $adminOnly,
            'appointment-tracking.prescriptions.update' => $adminOnly,
            'appointment-tracking.prescriptions.delete' => $adminOnly,
        ];
    }
}
