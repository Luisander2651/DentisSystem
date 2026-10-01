<?php

declare(strict_types=1);

namespace App\Core\Health;

use Closure;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Throwable;

/**
 * Spec 015 (CA11, OB9.a): /up only answers 200 when PostgreSQL and Redis respond.
 *
 * On failure it throws an exception of its own, without the driver's exception as previous,
 * so the report Laravel writes for /up carries no host, port or driver message. Laravel 12
 * does not pass connect_timeout to the PostgreSQL DSN, so reachability is probed first
 * with a short TCP connect.
 */
final class CheckDependenciesOnHealth
{
    private const CONNECT_TIMEOUT_SECONDS = 2.0;

    public function handle(DiagnosingHealth $event): void
    {
        $this->check('pgsql', fn () => $this->probePostgres());
        $this->check('redis', fn () => Redis::connection()->ping());
    }

    private function check(string $dependency, Closure $probe): void
    {
        try {
            $probe();
        } catch (Throwable) {
            Log::error('health.dependency_failed', ['dependency' => $dependency]);

            throw new RuntimeException("{$dependency} unavailable");
        }
    }

    private function probePostgres(): void
    {
        $connection = config('database.connections.'.config('database.default'));
        $socket = @fsockopen(
            (string) $connection['host'],
            (int) $connection['port'],
            $errorCode,
            $errorMessage,
            self::CONNECT_TIMEOUT_SECONDS,
        );

        if ($socket === false) {
            throw new RuntimeException('PostgreSQL is not reachable');
        }
        fclose($socket);

        DB::connection()->select('select 1');
    }
}
