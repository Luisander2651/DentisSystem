<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LogoutUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class LogoutController
{
    public function __construct(
        private LogoutUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $expiredCookie = cookie(
            name: 'auth_token',
            value: '',
            minutes: -60,
            path: (string) config('session.path', '/'),
            domain: config('session.domain'),
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: (string) config('session.same_site', 'lax'),
        );

        try {
            $this->useCase->execute($request->user(), $request->bearerToken());

            return response()->json([
                'message' => 'Logout successful',
            ], 200)->withCookie($expiredCookie);
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 409)->withCookie($expiredCookie);
        } catch (AuthAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 401)->withCookie($expiredCookie);
        } catch (\Exception $e) {
            Log::error('LogoutController: unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500)->withCookie($expiredCookie);
        }
    }
}
