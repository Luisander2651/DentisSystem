<?php

declare(strict_types=1);

use App\Core\Authorization\Exceptions\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 014, the safety net of bootstrap/app.php (withExceptions) for api/*. The routes are
 * registered only inside the test and have no try/catch of their own, so the net keeps being
 * exercised even after every real controller catches its own errors.
 */
const GLOBAL_FALLBACK_TEST_PHONE = '+52 555 010 7777';

beforeEach(function () {
    Route::middleware(['api', 'throttle:api'])->prefix('api/v1/__fallback-test')->group(function (): void {
        Route::get('/unexpected', fn () => throw new RuntimeException('detalle interno'));
        Route::get('/query', fn () => throw new QueryException(
            'pgsql',
            'select * from contact_info where phone_number = ?',
            [GLOBAL_FALLBACK_TEST_PHONE],
            new PDOException('SQLSTATE[08006] connection failure'),
        ));
        Route::get('/forbidden', fn () => throw AuthorizationException::forbidden('fallback.test'));
        Route::post('/validation', fn (Request $request) => $request->validate(['name' => ['required']]));
        Route::get('/reported', function () {
            report(new RuntimeException('Fallo con el telefono '.GLOBAL_FALLBACK_TEST_PHONE));

            return response()->json(['ok' => true]);
        });
    });

    $this->logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $this->logged->push($entry));
});

it('answers a generic 500 for an exception nobody caught', function () {
    $this->getJson('/api/v1/__fallback-test/unexpected')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('logs an uncaught failed query once, without the patient data of its bindings', function () {
    $this->getJson('/api/v1/__fallback-test/query')->assertStatus(500);

    $unexpected = $this->logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error');
    $everything = $this->logged
        ->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))
        ->implode("\n");

    expect($unexpected)->toHaveCount(1)
        ->and($this->logged)->toHaveCount(1)
        ->and($everything)->not->toContain(GLOBAL_FALLBACK_TEST_PHONE);
});

it('answers 403 with an error body for an authorization exception nobody caught', function () {
    $this->getJson('/api/v1/__fallback-test/forbidden')
        ->assertForbidden()
        ->assertJsonStructure(['error'])
        ->assertJsonMissingPath('message');
});

it('keeps the framework validation response', function () {
    $this->postJson('/api/v1/__fallback-test/validation', [])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['name']]);
});

it('keeps the framework rate limit response', function () {
    foreach (range(1, 10) as $attempt) {
        $this->postJson('/api/v1/__fallback-test/validation', ['name' => 'ok']);
    }

    $this->postJson('/api/v1/__fallback-test/validation', ['name' => 'ok'])->assertTooManyRequests();
});

it('answers 401 in JSON on the api without a session, even without asking for JSON', function () {
    $this->get('/api/v1/patients')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('keeps a sanitized trace of an exception reported manually during an api request (spec 014, R1)', function () {
    $this->getJson('/api/v1/__fallback-test/reported')->assertOk();

    $everything = $this->logged
        ->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))
        ->implode("\n");

    expect($this->logged)->toHaveCount(1)
        ->and($this->logged->first()->message)->toBe('unexpected_error')
        ->and($this->logged->first()->context)->toHaveKeys(['exception', 'origin'])
        ->and($everything)->not->toContain(GLOBAL_FALLBACK_TEST_PHONE);
});
