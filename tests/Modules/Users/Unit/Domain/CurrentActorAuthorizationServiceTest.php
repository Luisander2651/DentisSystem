<?php

declare(strict_types=1);

use App\Core\Authorization\CurrentActorAuthorizationService;
use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class, ActingAsStaff::class, ActingAsPatient::class);

/**
 * The second authorisation layer, tested directly rather than through a route: this is
 * what protects a use case invoked programmatically, where only.admin never runs.
 */
beforeEach(function () {
    $this->service = new CurrentActorAuthorizationService;
});

it('allows every users permission for an active administrator', function (string $permission) {
    $this->actingAsAdmin();

    $this->service->assertCan($permission);

    expect(true)->toBeTrue();
})->with(['users.create', 'users.update', 'users.delete', 'users.view', 'users.manage']);

it('refuses when nobody is authenticated', function () {
    expect(fn () => $this->service->assertCan('users.view'))
        ->toThrow(AuthorizationException::class, 'Authentication is required.');
});

it('refuses a patient, who is not a staff model at all', function () {
    $this->actingAsPatient();

    expect(fn () => $this->service->assertCan('users.view'))
        ->toThrow(AuthorizationException::class, 'Authentication is required.');
});

it('refuses an inactive administrator before looking at the role', function () {
    $this->actingAsInactiveAdmin();

    expect(fn () => $this->service->assertCan('users.view'))
        ->toThrow(AuthorizationException::class, 'Your account is inactive.');
});

it('refuses a staff member who is not an administrator', function (string $role) {
    $this->actingAsNonAdminUser($role);

    expect(fn () => $this->service->assertCan('users.view'))
        ->toThrow(AuthorizationException::class);
})->with(['Asistente', 'Doctor']);

it('refuses a permission that is not in the catalogue', function () {
    // The permission list is a closed allow-list, so an unknown string is denied even for
    // an administrator - a typo in a use case fails loudly instead of granting access.
    $this->actingAsAdmin();

    expect(fn () => $this->service->assertCan('users.impersonate'))
        ->toThrow(AuthorizationException::class);
});

it('matches the administrator role case-insensitively', function () {
    // BR-16: the roles table stores 'Administrador' with a capital A, and both
    // authorisation layers lower-case before comparing, which is why the capitalisation
    // never mattered.
    $this->actingAsAdmin();

    $this->service->assertCan('users.view');

    expect(true)->toBeTrue();
});

it('answers can() without throwing for every kind of actor', function (?string $actor, string $permission, bool $expected) {
    // Spec 018: use cases ask can() to choose between the general and the "own" permission,
    // so it must answer false - never throw - for anyone who does not hold the permission.
    match ($actor) {
        null => null,
        'paciente' => $this->actingAsPatient(),
        'inactivo' => $this->actingAsInactiveAdmin(),
        'Administrador' => $this->actingAsAdmin(),
        default => $this->actingAsNonAdminUser($actor),
    };

    expect($this->service->can($permission))->toBe($expected);
})->with([
    'sin sesión' => [null, 'appointments.view', false],
    'paciente' => ['paciente', 'appointments.view', false],
    'staff inactivo' => ['inactivo', 'appointments.view', false],
    'administrador con permiso' => ['Administrador', 'appointments.view', true],
    'administrador sin permiso propio' => ['Administrador', 'appointments.view-own', false],
    'asistente con permiso' => ['Asistente', 'patients.clinical-data.manage', true],
    'asistente sin permiso' => ['Asistente', 'appointment-tracking.create', false],
    'doctor con permiso' => ['Doctor', 'patients.view', true],
    'doctor sin permiso general' => ['Doctor', 'appointments.view', false],
    'permiso inexistente' => ['Administrador', 'users.impersonate', false],
]);

it('answers can() with the permissions spec 018 adds to the map', function (string $role, string $permission, bool $expected) {
    $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    expect($this->service->can($permission))->toBe($expected);
})->with([
    'asistente agenda' => ['Asistente', 'appointments.create', true],
    'doctor con permiso propio' => ['Doctor', 'appointments.view-own', true],
    'doctor completa los suyos' => ['Doctor', 'appointment-tracking.create-own', true],
    'asistente sin permiso propio' => ['Asistente', 'appointments.view-own', false],
])->skip('018: pendiente de T021');

it('returns the id of the acting staff member', function (string $role) {
    $user = $role === 'Administrador' ? $this->actingAsAdmin() : $this->actingAsNonAdminUser($role);

    expect($this->service->currentActorId())->toBe((string) $user->id);
})->with(['Administrador', 'Asistente', 'Doctor']);

it('refuses currentActorId() without a staff actor', function (?string $actor) {
    if ($actor === 'paciente') {
        $this->actingAsPatient();
    }

    expect(fn () => $this->service->currentActorId())
        ->toThrow(AuthorizationException::class, 'Authentication is required.');
})->with(['sin sesión' => [null], 'paciente' => ['paciente']]);
