<?php

declare(strict_types=1);

namespace App\Core\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InjectSanctumTokenFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null) {
            $tokenFromCookie = $request->cookie('auth_token');

            if (is_string($tokenFromCookie) && $tokenFromCookie !== '') {
                $request->headers->set('Authorization', 'Bearer '.trim($tokenFromCookie));
            }
        }

        return $next($request);
    }
}
