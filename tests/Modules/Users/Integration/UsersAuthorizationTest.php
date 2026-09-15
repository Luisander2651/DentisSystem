<?php

declare(strict_types=1);

use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

beforeEach(function () {
    $this->withoutRateLimiting();
});

/**
 * BR-1: every route is guarded twice - the only.admin middleware at the HTTP boundary and
 * assertCan() inside each use case. The redundancy is deliberate, so the second layer
 * still protects a use case invoked programmatically.
 *
 * @return list<array{0:string,1:string}>
 */
function usersRoutes(): array
{
    return [
        'create' => ['postJson', ''],
        'update' => ['putJson', '/00000000-0000-4000-8000-000000000000'],
        'list' => ['getJson', ''],
        'delete' => ['deleteJson', '/00000000-0000-4000-8000-000000000000'],
    ];
}

it('answers 401 on every route without authentication', function (string $method, string $suffix) {
    $this->{$method}($this->usersUrl().$suffix)->assertUnauthorized();
})->with(usersRoutes());

it('answers 403 on every route for a staff member who is not an administrator', function (string $method, string $suffix) {
    $this->actingAsNonAdminUser('Asistente');

    $this->{$method}($this->usersUrl().$suffix)->assertForbidden();
})->with(usersRoutes());

it('answers 403 on every route for a doctor', function (string $method, string $suffix) {
    $this->actingAsNonAdminUser('Doctor');

    $this->{$method}($this->usersUrl().$suffix)->assertForbidden();
})->with(usersRoutes());

it('answers 403 on every route for an inactive administrator', function (string $method, string $suffix) {
    // The role is right but the account is not: status is checked before the role in both
    // layers, so a deactivated administrator loses access immediately.
    $this->actingAsInactiveAdmin();

    $this->{$method}($this->usersUrl().$suffix)->assertForbidden();
})->with(usersRoutes());

it('answers 403 on every route for an authenticated patient', function (string $method, string $suffix) {
    // A patient is not a UserModel at all, which is the first check in both layers.
    $this->actingAsPatient();

    $this->{$method}($this->usersUrl().$suffix)->assertForbidden();
})->with(usersRoutes());

it('tells a non-administrator why, without revealing anything else', function () {
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson($this->usersUrl())
        ->assertForbidden()
        ->assertJsonPath('error', 'Only administrators can access this resource.');
});

it('tells an inactive administrator that the account is the problem', function () {
    $this->actingAsInactiveAdmin();

    $this->getJson($this->usersUrl())
        ->assertForbidden()
        ->assertJsonPath('error', 'Your account is inactive.');
});
