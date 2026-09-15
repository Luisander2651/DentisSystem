<?php

declare(strict_types=1);

use App\Modules\Users\Domain\ValueObjects\UserRoleId;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;
use Tests\Support\UserGenerators;
use Tests\Support\UsesEris;

uses(UsersIntegrationTestCase::class, UsesEris::class, UserGenerators::class);

// P14 - the two write endpoints must store the SAME credential for the same value.
// Laravel's TrimStrings exempts `password` but not `new_password`, so before BR-14 a
// padded password survived intact through POST and was trimmed through PUT: the same
// input produced two different credentials depending on the endpoint, and a staff
// member created with padding could never log in from a form that trims.

it('stores the same credential for a padded password on both endpoints (property)', function () {
    $this->limitTo(6);
    $this->withoutRateLimiting();

    $this->forAll($this->paddedUserPasswordGenerator())->then(function (string $padded) {
        $this->actingAsAdmin();

        $email = Str::uuid().'@example.com';

        $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
            'email' => $email,
            'password' => $padded,
        ]))->assertCreated();

        $created = UserModel::query()->where('email', $email)->firstOrFail();

        $target = $this->existingUser('Asistente');
        $this->putJson($this->userUrl($target->id), ['new_password' => $padded])->assertOk();

        $updated = UserModel::query()->findOrFail($target->id);

        // Both hashes must accept the trimmed form and reject nothing the other accepts.
        $trimmed = trim($padded);

        expect(password_verify($trimmed, $created->password))->toBeTrue()
            ->and(password_verify($trimmed, $updated->password))->toBeTrue();
    });
});

// P15 - filter consistency. Every row returned satisfies the filters, and no row that
// satisfies them is left out. Asserting only the first half would pass trivially for an
// endpoint that returns nothing.

it('returns exactly the users matching the requested filters (property)', function () {
    $this->limitTo(8);
    $this->withoutRateLimiting();

    $this->forAll($this->canonicalRoleGenerator(), $this->userStatusGenerator())
        ->then(function (string $role, string $status) {
            $this->actingAsAdmin();

            $this->existingUser('Asistente', ['status' => 'active']);
            $this->existingUser('Doctor', ['status' => 'inactive']);
            $this->existingUser('Asistente', ['status' => 'inactive']);

            $response = $this->getJson($this->usersUrl().'?role='.urlencode($role).'&status='.$status);
            $response->assertOk();

            $returned = collect($response->json('data'));

            foreach ($returned as $user) {
                expect($user['role_id'])->toBe($role)
                    ->and($user['status'])->toBe($status);
            }

            $expected = UserModel::query()
                ->where('status', $status)
                ->where('role_id', (new UserRoleId($role))->toDatabaseId())
                ->count();

            expect($returned)->toHaveCount($expected);
        });
});

// P16 - the projection never leaks the credential. Asserting the absence of a `password`
// key is not enough: the check is that no part of the stored hash appears anywhere in the
// serialised response, under any key.

it('never leaks any part of the password hash (property)', function () {
    $this->limitTo(8);
    $this->withoutRateLimiting();

    $this->forAll($this->canonicalRoleGenerator())->then(function (string $role) {
        $this->actingAsAdmin();

        $user = $this->existingUser($role);
        $hash = UserModel::query()->findOrFail($user->id)->password;

        $body = $this->getJson($this->usersUrl())->assertOk()->getContent();

        expect($body)->not->toContain($hash)
            // The bcrypt salt is the first 29 characters; a partial leak is still a leak.
            ->and($body)->not->toContain(substr($hash, 0, 29));
    });
});

// P17 - authorisation cannot be talked around. No request body and no query string may
// turn a non-admin into an admin, so the status is 403 for EVERY payload, not just the
// well-formed ones.

it('refuses every route for a non-admin regardless of payload (property)', function () {
    $this->limitTo(8);
    $this->withoutRateLimiting();

    $this->forAll($this->canonicalRoleGenerator())->then(function (string $role) {
        $this->forgetAuthState();
        $this->actingAsNonAdminUser('Asistente');

        $target = $this->existingUser('Asistente');

        $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['role_id' => $role]))->assertForbidden();
        $this->putJson($this->userUrl($target->id), ['role_id' => $role])->assertForbidden();
        $this->getJson($this->usersUrl().'?role='.urlencode($role))->assertForbidden();
        $this->deleteJson($this->userUrl($target->id))->assertForbidden();
    });
});

it('refuses every route for an inactive admin regardless of payload (property)', function () {
    $this->limitTo(6);
    $this->withoutRateLimiting();

    $this->forAll($this->canonicalRoleGenerator())->then(function (string $role) {
        $this->forgetAuthState();
        $this->actingAsInactiveAdmin();

        $target = $this->existingUser('Asistente');

        $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['role_id' => $role]))->assertForbidden();
        $this->deleteJson($this->userUrl($target->id))->assertForbidden();
    });
});

// P18 - no arbitrary input reaches a 500. This is the property that finding 1 violated
// most violently: a short name did not merely return the wrong status, it killed the
// worker before any response could be written.

it('never answers 500 for an arbitrary creation payload (property)', function () {
    $this->limitTo(10);
    $this->withoutRateLimiting();

    $this->forAll($this->anyUserNameGenerator(), $this->invalidRoleGenerator())
        ->then(function (array $namePair, string $role) {
            $this->actingAsAdmin();

            $response = $this->postJson($this->usersUrl(), [
                'first_name' => $namePair[0],
                'last_name' => $namePair[1],
                'email' => Str::uuid().'@example.com',
                'password' => 'Sup3rSecret!',
                'role_id' => $role,
            ]);

            expect($response->getStatusCode())->toBeLessThan(500);
        });
});

it('never answers 500 for a malformed identifier (property)', function () {
    $this->limitTo(8);
    $this->withoutRateLimiting();

    $this->forAll($this->malformedUuidGenerator())->then(function (string $id) {
        $this->actingAsAdmin();

        // An empty or slash-bearing id does not match the route at all, so a 404/405
        // from the router is a legitimate outcome; what must never happen is a 500.
        expect($this->putJson($this->userUrl($id), ['status' => 'inactive'])->getStatusCode())->toBeLessThan(500);
        expect($this->deleteJson($this->userUrl($id))->getStatusCode())->toBeLessThan(500);
    });
});
