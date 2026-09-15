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
