<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

beforeEach(function () {
    $this->withoutRateLimiting();
    $this->admin = $this->actingAsAdmin();
});

it('updates a single field and leaves the rest untouched', function () {
    $user = $this->existingUser('Asistente', ['first_name' => 'Ana', 'last_name' => 'Ruiz']);

    $this->putJson($this->userUrl($user->id), ['first_name' => 'Luis'])
        ->assertOk()
        ->assertJson(['message' => 'User updated successfully']);

    $updated = UserModel::query()->findOrFail($user->id);

    expect($updated->first_name)->toBe('Luis')
        ->and($updated->last_name)->toBe('Ruiz')
        ->and($updated->role_id)->toBe(2)
        ->and($updated->status)->toBe('active')
        ->and($updated->email)->toBe($user->email);
});

it('updates the role', function () {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), ['role_id' => 'Doctor'])->assertOk();

    expect(UserModel::query()->findOrFail($user->id)->role_id)->toBe(3);
});

it('updates the status', function () {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), ['status' => 'inactive'])->assertOk();

    expect(UserModel::query()->findOrFail($user->id)->status)->toBe('inactive');
});

it('replaces the password so the previous one stops authenticating', function () {
    $user = $this->existingUser('Asistente');

    expect(password_verify('password', UserModel::query()->findOrFail($user->id)->password))->toBeTrue();

    $this->putJson($this->userUrl($user->id), ['new_password' => 'An0therSecret!'])->assertOk();

    $stored = UserModel::query()->findOrFail($user->id)->password;

    expect(password_verify('An0therSecret!', $stored))->toBeTrue()
        ->and(password_verify('password', $stored))->toBeFalse();
});

// BR-14: `new_password` is NOT exempt from TrimStrings while `password` is, so before the
// fix the same padded value produced one credential through POST and a different one
// through PUT.
it('trims the new password exactly as creation does', function () {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), ['new_password' => '   An0therSecret!   '])->assertOk();

    $stored = UserModel::query()->findOrFail($user->id)->password;

    expect(password_verify('An0therSecret!', $stored))->toBeTrue()
        ->and(password_verify('   An0therSecret!   ', $stored))->toBeFalse();
});

it('rejects a new password below eight characters', function () {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), ['new_password' => 'short7c'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['new_password']);
});

// BR-18: a missing user is 404, where this endpoint used to answer 409 Conflict.
it('answers 404 for a user that does not exist', function () {
    $this->putJson($this->userUrl($this->missingUserId()), ['first_name' => 'Luis'])
        ->assertNotFound();
});

// BR-19: UuidIdentifier throws a plain InvalidArgumentException, which used to reach the
// generic handler and answer 500.
it('answers 400 for a malformed identifier', function () {
    $this->putJson($this->userUrl('not-a-uuid'), ['first_name' => 'Luis'])
        ->assertStatus(400)
        ->assertJsonPath('error', 'The provided user id is not valid.');
});

// BR-22: an update with nothing to update is a malformed request, not a conflict.
it('answers 422 when no usable field is sent', function (array $payload) {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), $payload)->assertUnprocessable();
})->with([
    'empty body' => [[]],
    'only empty strings' => [['first_name' => '', 'status' => '']],
]);

// BR-23: the email is immutable (BR-4) and used to be discarded in silence, so the client
// believed it had changed an address when nothing had happened.
it('rejects an attempt to change the email', function () {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), ['email' => 'nuevo@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    expect(UserModel::query()->findOrFail($user->id)->email)->toBe($user->email);
});

it('rejects an invalid role or status', function (array $payload, string $field) {
    $user = $this->existingUser('Asistente');

    $this->putJson($this->userUrl($user->id), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'legacy role vocabulary' => [['role_id' => 'admin'], 'role_id'],
    'unknown status' => [['status' => 'activo'], 'status'],
]);

// BR-21, identity rule. An administrator may not take away their own access.
it('refuses to let an administrator change their own role', function () {
    $this->putJson($this->userUrl($this->admin->id), ['role_id' => 'Asistente'])
        ->assertForbidden();

    expect(UserModel::query()->findOrFail($this->admin->id)->role_id)->toBe(1);
});

it('refuses to let an administrator deactivate themselves', function () {
    $this->putJson($this->userUrl($this->admin->id), ['status' => 'inactive'])
        ->assertForbidden();

    expect(UserModel::query()->findOrFail($this->admin->id)->status)->toBe('active');
});

it('still lets an administrator change their own name and password', function () {
    $this->putJson($this->userUrl($this->admin->id), [
        'first_name' => 'Luisa',
        'new_password' => 'An0therSecret!',
    ])->assertOk();

    $updated = UserModel::query()->findOrFail($this->admin->id);

    expect($updated->first_name)->toBe('Luisa')
        ->and(password_verify('An0therSecret!', $updated->password))->toBeTrue();
});

// The limit BR-21 deliberately does NOT cover, pinned so the decision stays visible.
it('does not stop an administrator from deactivating another administrator', function () {
    $other = $this->existingUser('Administrador');

    $this->putJson($this->userUrl($other->id), ['status' => 'inactive'])->assertOk();

    expect(UserModel::query()->findOrFail($other->id)->status)->toBe('inactive');
});

// The real client (resources/js/pages/usuarios/edit-user.js) submits every field on every
// save, leaving the untouched ones empty. Without treating '' as "not provided", renaming
// a user would be rejected for a password nobody meant to change.
it('accepts the full payload the edit form actually sends', function () {
    $user = $this->existingUser('Asistente', ['first_name' => 'Ana', 'last_name' => 'Ruiz']);

    $this->putJson($this->userUrl($user->id), [
        'first_name' => 'Luis',
        'last_name' => '',
        'role_id' => '',
        'status' => '',
        'new_password' => '',
    ])->assertOk();

    $updated = UserModel::query()->findOrFail($user->id);

    expect($updated->first_name)->toBe('Luis')
        ->and($updated->last_name)->toBe('Ruiz')
        ->and($updated->role_id)->toBe(2)
        ->and($updated->status)->toBe('active')
        ->and(password_verify('password', $updated->password))->toBeTrue();
});
