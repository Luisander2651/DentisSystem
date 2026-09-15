<?php

declare(strict_types=1);

use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

beforeEach(function () {
    $this->withoutRateLimiting();
    $this->admin = $this->actingAsAdmin();

    $this->asistente = $this->existingUser('Asistente', ['status' => 'active']);
    $this->doctor = $this->existingUser('Doctor', ['status' => 'inactive']);
});

it('returns every staff member when no filter is given', function () {
    // The acting administrator counts too: three rows in total.
    $this->getJson($this->usersUrl())
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters by status', function () {
    $response = $this->getJson($this->usersUrl().'?status=active')->assertOk();

    expect($response->json('data'))->toHaveCount(2);

    foreach ($response->json('data') as $user) {
        expect($user['status'])->toBe('active');
    }
});

it('filters by role', function () {
    $response = $this->getJson($this->usersUrl().'?role=Doctor')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($this->doctor->id);
});

it('accepts the role filter in any capitalisation', function () {
    expect($this->getJson($this->usersUrl().'?role=doctor')->assertOk()->json('data'))->toHaveCount(1);
});

it('combines both filters', function () {
    expect($this->getJson($this->usersUrl().'?role=Doctor&status=inactive')->assertOk()->json('data'))
        ->toHaveCount(1);
});

it('returns an empty collection with 200 when nothing matches', function () {
    // BR-7: no match is an empty result, not a 404.
    $this->getJson($this->usersUrl().'?role=Doctor&status=active')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('treats an empty filter as no filter', function () {
    $this->getJson($this->usersUrl().'?role=&status=')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('rejects an invalid filter value', function (string $query, string $field) {
    $this->getJson($this->usersUrl().'?'.$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'legacy role vocabulary' => ['role=admin', 'role'],
    'unknown status' => ['status=activo', 'status'],
]);

it('projects exactly the six public fields', function () {
    $user = collect($this->getJson($this->usersUrl())->assertOk()->json('data'))
        ->firstWhere('id', $this->asistente->id);

    expect(array_keys($user))
        ->toEqualCanonicalizing(['id', 'first_name', 'last_name', 'email', 'role_id', 'status'])
        ->and($user['role_id'])->toBe('Asistente');
});

// BR-8 / BR-15: asserting the absence of a `password` key is not enough - no fragment of
// the stored hash may appear anywhere in the payload, under any key.
it('never exposes the password hash', function () {
    $hash = UserModel::query()->findOrFail($this->asistente->id)->password;
    $body = $this->getJson($this->usersUrl())->assertOk()->getContent();

    expect($body)->not->toContain($hash)
        // The bcrypt salt is the first 29 characters; a partial leak is still a leak.
        ->and($body)->not->toContain(substr($hash, 0, 29))
        ->and($body)->not->toContain('password');
});
