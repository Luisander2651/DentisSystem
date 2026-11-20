<?php

declare(strict_types=1);

use App\Modules\Users\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Users\Integration\UsersIntegrationTestCase;

uses(UsersIntegrationTestCase::class);

/**
 * Spec 028 (CA1, CA5, CA9, CA13, CA14; P6, P7): GET /api/v1/users answers one page of twelve with
 * its `meta` and the counts by role of the whole staff, whatever the filters.
 */
beforeEach(function () {
    $this->withoutRateLimiting();
    $this->seedStaff = function (int $count, string $role = 'Asistente', array $overrides = []): void {
        for ($i = 1; $i <= $count; $i++) {
            $this->createUserWithRole($role, array_merge(['first_name' => "{$role}{$i}"], $overrides))
                ->forceFill(['created_at' => Carbon::parse('2025-01-01 08:00:00')->addMinutes($i)])
                ->save();
        }
    };
});

it('answers the first page of twelve with its meta and the counts by role', function () {
    $this->actingAsAdmin();
    ($this->seedStaff)(20, 'Asistente');
    ($this->seedStaff)(9, 'Doctor', ['status' => 'inactive']);

    $this->getJson($this->usersUrl())
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
            'from' => 1,
            'to' => 12,
            'counts' => ['total' => 30, 'administrador' => 1, 'asistente' => 20, 'doctor' => 9],
        ]);
});

it('answers the page asked for and the last one for a page beyond it', function (string $query, int $page, int $count) {
    $this->actingAsAdmin();
    ($this->seedStaff)(29);

    $this->getJson($this->usersUrl().$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'third page' => ['?page=3', 3, 6],
    'beyond the last' => ['?page=999', 3, 6],
]);

it('never answers more than fifty staff members at once', function () {
    $this->actingAsAdmin();
    ($this->seedStaff)(59);

    $this->getJson($this->usersUrl().'?per_page=10000')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50);
});

it('keeps the counts by role of the whole staff with a filter', function () {
    $this->actingAsAdmin();
    ($this->seedStaff)(3, 'Doctor');
    ($this->seedStaff)(2, 'Asistente', ['status' => 'inactive']);

    $this->getJson($this->usersUrl().'?role=Doctor&status=active')
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.counts', ['total' => 6, 'administrador' => 1, 'asistente' => 2, 'doctor' => 3]);
});

it('answers an empty page without from nor to when nothing matches', function () {
    $this->actingAsAdmin();

    $this->getJson($this->usersUrl().'?role=Doctor')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null);
});

it('refuses with 403 and without meta whoever is not an administrator', function (string $actor) {
    ($this->seedStaff)(3);

    match ($actor) {
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'doctor' => $this->actingAsNonAdminUser('Doctor'),
        'paciente' => $this->actingAsPatient(),
        'inactivo' => $this->actingAsInactiveAdmin(),
    };

    $this->getJson($this->usersUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
})->with(['asistente', 'doctor', 'paciente', 'inactivo']);

it('rejects invalid page parameters with 422', function (string $query, string $field) {
    $this->actingAsAdmin();

    $this->getJson($this->usersUrl().$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'page zero' => ['?page=0', 'page'],
    'page array' => ['?page[]=1', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
    'per_page decimal' => ['?per_page=1.5', 'per_page'],
]);

it('answers a generic 500 logged once and without the message when reading the page fails', function () {
    $logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $logged->push($entry));
    $this->actingAsAdmin();
    $this->mock(UserRepositoryInterface::class)
        ->shouldReceive('findPage')->andThrow(new RuntimeException('staff.secret@example.com'));

    $this->getJson($this->usersUrl())
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);

    $unexpected = $logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error');

    expect($unexpected)->toHaveCount(1)
        ->and($logged->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))->implode("\n"))
        ->not->toContain('staff.secret');
});
