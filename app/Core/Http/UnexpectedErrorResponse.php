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
    public static function from(Throwable $exception, string $origin): JsonResponse
    {
        Log::error('unexpected_error', [
            'origin' => $origin,
            'exception' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return new JsonResponse(['error' => 'Internal server error'], 500);
    }
}
