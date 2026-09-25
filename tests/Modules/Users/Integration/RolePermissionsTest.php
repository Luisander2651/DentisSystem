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
    ];
}

beforeEach(function () {
    $this->service = new CurrentActorAuthorizationService;
});

it('grants each permission exactly to the active staff roles of the table', function (string $permission, array $allowedRoles) {
    foreach (['Administrador', 'Asistente', 'Doctor'] as $role) {
        $this->forgetAuthState();
        $this->actingAsNonAdminUser($role); // Si el metodo es llamado non admin, deberia realmente poder llamarse con admin???

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
