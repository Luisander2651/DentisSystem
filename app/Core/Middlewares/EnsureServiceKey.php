<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The attendance microservice reads appointments when an ESP32 scans a QR, with no user behind
 * the request, so it identifies itself with the `X-Service-Key` header instead of a token. With
 * no key configured every request is refused: the door never opens by a missing setting.
 */
final class EnsureServiceKey
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::hasValidKey($request)) {
            return new JsonResponse(['error' => 'Invalid service key.'], 401);
        }

        return $next($request);
    }

    /**
     * Also read by the `internal-service` rate limiter, which runs before this middleware.
     */
    public static function hasValidKey(Request $request): bool
    {
        $expectedKey = (string) config('services.attendance.key');
        $givenKey = (string) $request->header('X-Service-Key', '');

        return $expectedKey !== '' && hash_equals($expectedKey, $givenKey);
    }
}
