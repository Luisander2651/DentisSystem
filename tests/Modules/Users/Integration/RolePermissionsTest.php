<?php

declare(strict_types=1);

use App\Core\Authorization\CurrentActorAuthorizationService;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

/**
 * Spec 014: the permission map of CurrentActorAuthorizationService is the single source of
 * truth for the "Permisos esperados" table. Each permission lists the staff roles that hold
 * it; every other role, an inactive account and a patient are refused.
 *
 * @return array<string, array{0: string, 1: list<string>}>
 */
function rolePermissionMatrix(): array
{
    $allStaff = ['Administrador', 'Asistente', 'Doctor'];

    return [
        'patients.view' => ['patients.view', $allStaff],
        'patients.record.view' => ['patients.record.view', $allStaff],
        'appointments.patient-history.view' => ['appointments.patient-history.view', $allStaff],
        'appointments.view-detail' => ['appointments.view-detail', $allStaff],
        'patients.clinical-data.manage' => ['patients.clinical-data.manage', ['Administrador', 'Asistente']],
        'patients.update' => ['patients.update', ['Administrador']],
        'patients.create' => ['patients.create', ['Administrador']],
        'patients.delete' => ['patients.delete', ['Administrador']],
        'appointments.view' => ['appointments.view', ['Administrador']],
        'appointments.create' => ['appointments.create', ['Administrador']],
        'appointments.update' => ['appointments.update', ['Administrador']],
        'appointments.delete' => ['appointments.delete', ['Administrador']],
        'agenda.selectors.view' => ['agenda.selectors.view', ['Administrador']],
        'treatments.view' => ['treatments.view', ['Administrador']],
        'users.view' => ['users.view', ['Administrador']],
        'users.create' => ['users.create', ['Administrador']],
        'users.update' => ['users.update', ['Administrador']],
        'users.delete' => ['users.delete', ['Administrador']],
        'users.manage' => ['users.manage', ['Administrador']],
        'manage.certifications' => ['manage.certifications', ['Administrador']],
        'manage.gallery' => ['manage.gallery', ['Administrador']],
        'manage.promotions' => ['manage.promotions', ['Administrador']],
        'manage.testimonials' => ['manage.testimonials', ['Administrador']],
        'treatments.create' => ['treatments.create', ['Administrador']],
        'treatments.update' => ['treatments.update', ['Administrador']],
        'treatments.delete' => ['treatments.delete', ['Administrador']],
        'appointment-tracking.create' => ['appointment-tracking.create', ['Administrador']],
        'appointment-tracking.view' => ['appointment-tracking.view', ['Administrador']],
        'appointment-tracking.update' => ['appointment-tracking.update', ['Administrador']],
        'appointment-tracking.prescriptions.create' => ['appointment-tracking.prescriptions.create', ['Administrador']],
        'appointment-tracking.prescriptions.update' => ['appointment-tracking.prescriptions.update', ['Administrador']],
        'appointment-tracking.prescriptions.delete' => ['appointment-tracking.prescriptions.delete', ['Administrador']],
    ];
}

beforeEach(function () {
    $this->service = new CurrentActorAuthorizationService;
});

it('grants each permission exactly to the active staff roles of the table', function (string $permission, array $allowedRoles) {
    foreach (['Administrador', 'Asistente', 'Doctor'] as $role) {
        $this->forgetAuthState();
        $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

        $call = fn () => $this->service->assertCan($permission);

        if (in_array($role, $allowedRoles, true)) {
            expect($call)->not->toThrow(AuthorizationException::class);
        } else {
            expect($call)->toThrow(AuthorizationException::class);
        }
    }
})->with(rolePermissionMatrix());

it('refuses every permission to an inactive administrator', function (string $permission) {
    $this->actingAsInactiveAdmin();

    expect(fn () => $this->service->assertCan($permission))
        ->toThrow(AuthorizationException::class, 'Your account is inactive.');
})->with(array_map(fn (array $row): array => [$row[0]], rolePermissionMatrix()));

it('refuses every permission to an authenticated patient', function (string $permission) {
    $this->actingAsPatient();

    expect(fn () => $this->service->assertCan($permission))
        ->toThrow(AuthorizationException::class);
})->with(array_map(fn (array $row): array => [$row[0]], rolePermissionMatrix()));

it('covers every permission of the map, so the matrix cannot drift from it', function () {
    $permissions = (fn (): array => $this->permissions())->call(new CurrentActorAuthorizationService);

    expect(array_keys($permissions))->toEqualCanonicalizing(array_keys(rolePermissionMatrix()));
});

it('denies by default a permission that is not in the map', function (string $role) {
    $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    expect(fn () => $this->service->assertCan('permission.that.does.not.exist'))
        ->toThrow(AuthorizationException::class);
})->with(['Administrador', 'Asistente', 'Doctor']);
