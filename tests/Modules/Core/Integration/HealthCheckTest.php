<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA11 (OB9.a): /up answers 200 only when the app, PostgreSQL and Redis respond,
 * and fails in under 5 seconds when one of them does not, without exposing hosts or the
 * driver's error text in the body or in the logs. 192.0.2.1 is a documentation address
 * that never answers.
 */

beforeEach(function () {
    config(['app.debug' => false]);

    $this->logFile = storage_path('logs/health-'.Str::uuid().'.log');
    config(['logging.channels.health_capture' => ['driver' => 'single', 'path' => $this->logFile, 'level' => 'debug']]);
    Log::setDefaultDriver('health_capture');

    $this->originalPgsql = config('database.connections.pgsql');
    $this->originalRedis = config('database.redis.default');
});

afterEach(function () {
    config(['database.connections.pgsql' => $this->originalPgsql, 'database.redis.default' => $this->originalRedis]);
    DB::purge('pgsql');
    Redis::purge('default');

    if (is_file($this->logFile)) {
        unlink($this->logFile);
    }
});

/**
 * @return array{0: TestResponse, 1: float}
 */
function timedHealthCheck($test): array
{
    $start = microtime(true);
    $response = $test->get('/up');

    return [$response, microtime(true) - $start];
}

function expectNothingInternal(string $text, string $host): void
{
    expect($text)->not->toContain($host)
        ->and($text)->not->toContain('SQLSTATE')
        ->and($text)->not->toContain('Connection refused')
        ->and($text)->not->toContain('timed out');
}

it('answers 200 when PostgreSQL and Redis respond', function () {
    $this->get('/up')->assertOk();
});

it('fails fast without internal details when PostgreSQL does not respond', function () {
    config(['database.connections.pgsql.host' => '192.0.2.1']);
    DB::purge('pgsql');

    [$response, $seconds] = timedHealthCheck($this);

    expect($response->getStatusCode())->toBe(500)
        ->and($seconds)->toBeLessThan(5.0);
    expectNothingInternal($response->getContent(), '192.0.2.1');
    expectNothingInternal((string) @file_get_contents($this->logFile), '192.0.2.1');
});

it('fails fast without internal details when the Redis host does not respond', function () {
    config(['database.redis.default.host' => '192.0.2.1']);
    Redis::purge('default');

    [$response, $seconds] = timedHealthCheck($this);

    expect($response->getStatusCode())->toBe(500)
        ->and($seconds)->toBeLessThan(5.0);
    expectNothingInternal($response->getContent(), '192.0.2.1');
    expectNothingInternal((string) @file_get_contents($this->logFile), '192.0.2.1');
});

it('fails fast without internal details when the Redis port is closed', function () {
    config(['database.redis.default.port' => 6390]);
    Redis::purge('default');

    [$response, $seconds] = timedHealthCheck($this);

    expect($response->getStatusCode())->toBe(500)
        ->and($seconds)->toBeLessThan(5.0);
    expectNothingInternal($response->getContent(), ':6390');
    expectNothingInternal((string) @file_get_contents($this->logFile), ':6390');
});
