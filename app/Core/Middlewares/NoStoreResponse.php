<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec 025 (CA20, TM10): appointments, prescriptions and QR codes of the portal must not stay
 * in a browser or proxy cache. It runs before authentication (bootstrap/app.php), so the 401
 * and 403 answers of the group carry the header too.
 */
final class NoStoreResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
