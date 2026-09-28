<?php

declare(strict_types=1);

namespace App\Core\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Spec 014 (P7, P11): the single answer to an unexpected error. The client only learns that
 * something failed; the team gets where and what, but never the exception message, which may
 * carry patient data (the bindings of a failed query, the value inside a domain exception).
 */
final class UnexpectedErrorResponse
{
    /**
     * For a controller that caught the error: logs it once and answers the generic 500.
     */
    public static function from(Throwable $exception, string $origin): JsonResponse
    {
        self::log($exception, $origin);

        return self::response();
    }

    /**
     * The sanitized log of an unexpected error. The API safety net in bootstrap/app.php calls it
     * from report(), so it also covers errors that are reported but never rendered.
     */
    public static function log(Throwable $exception, string $origin): void
    {
        Log::error('unexpected_error', [
            'origin' => $origin,
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    public static function response(): JsonResponse
    {
        return new JsonResponse(['error' => 'Internal server error'], 500);
    }
}
