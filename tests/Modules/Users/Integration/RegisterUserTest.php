<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Str;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

beforeEach(function () {
    $this->withoutRateLimiting();
    $this->actingAsAdmin();
});

it('creates a staff member and persists every field', function () {
    $email = Str::uuid().'@example.com';

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'first_name' => 'aNA',
        'last_name' => 'rUIZ',
        'email' => $email,
        'password' => 'Sup3rSecret!',
        'role_id' => 'Doctor',
    ]))->assertCreated()
        ->assertJson(['message' => 'User registered successfully']);

    $created = UserModel::query()->where('email', $email)->firstOrFail();

    expect($created->first_name)->toBe('Ana')
        ->and($created->last_name)->toBe('Ruiz')
        // BR-3: an account is always created active.
        ->and($created->status)->toBe('active')
        ->and($created->role_id)->toBe(3)
        ->and(Str::isUuid($created->id))->toBeTrue()
        // The stored credential must actually authenticate, not merely look like a hash.
        ->and(password_verify('Sup3rSecret!', $created->password))->toBeTrue();
});

it('accepts the role in any capitalisation', function (string $submitted, int $expectedRoleId) {
    $email = Str::uuid().'@example.com';

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'email' => $email,
        'role_id' => $submitted,
    ]))->assertCreated();

    expect(UserModel::query()->where('email', $email)->firstOrFail()->role_id)->toBe($expectedRoleId);
})->with([
    ['administrador', 1],
    ['ASISTENTE', 2],
    ['dOcToR', 3],
]);

it('refuses an email already used by another staff member', function () {
    $existing = $this->existingUser('Asistente');

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['email' => $existing->email]))
        ->assertStatus(409)
        ->assertJsonPath('error', "The email {$existing->email} is already in use.");
});

// BR-10 / BR-12. This is the case that used to kill the worker: an empty name reached
// UserName::create('', ''), which recursed until PHP died, so no response was ever
// written. It now stops at validation.
it('rejects an empty name without crashing the request', function () {
    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'first_name' => '',
        'last_name' => '',
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name']);
});

it('rejects a name whose combined length exceeds the maximum', function () {
    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'first_name' => str_repeat('x', 60),
    ]))->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name']);
});

it('rejects a body with no fields at all', function () {
    $this->postJson($this->usersUrl(), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'role_id']);
});

it('rejects a malformed email', function () {
    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// BR-11 (SECURITY-12): the 8-character floor Unit 3 applied to the public registration,
// finally applied to staff creation too.
it('rejects a password below eight characters', function (string $password) {
    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['password' => $password]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
})->with(['', 'a', 'short7c']);

it('rejects the pre-BR-16 role vocabulary', function (string $legacy) {
    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload(['role_id' => $legacy]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role_id']);
})->with(['admin', 'asistent', 'recepcionista']);

// BR-14: `password` is exempt from Laravel's TrimStrings middleware, so without the
// explicit trim the padded value would be hashed verbatim and the staff member could
// never sign in from a form that trims.
it('trims the surrounding whitespace of the password', function () {
    $email = Str::uuid().'@example.com';

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'email' => $email,
        'password' => '   Sup3rSecret!   ',
    ]))->assertCreated();

    $created = UserModel::query()->where('email', $email)->firstOrFail();

    expect(password_verify('Sup3rSecret!', $created->password))->toBeTrue()
        ->and(password_verify('   Sup3rSecret!   ', $created->password))->toBeFalse();
});

it('preserves whitespace inside the password', function () {
    $email = Str::uuid().'@example.com';

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'email' => $email,
        'password' => 'con espacios internos',
    ]))->assertCreated();

    expect(password_verify('con espacios internos', UserModel::query()->where('email', $email)->firstOrFail()->password))
        ->toBeTrue();
});

// BR-20: the DTO used to carry a $status the use case never read, advertising a
// capability that did not exist.
it('ignores a status sent at creation time', function () {
    $email = Str::uuid().'@example.com';

    $this->postJson($this->usersUrl(), $this->validRegisterUserPayload([
        'email' => $email,
        'status' => 'inactive',
    ]))->assertCreated();

    expect(UserModel::query()->where('email', $email)->firstOrFail()->status)->toBe('active');
});
