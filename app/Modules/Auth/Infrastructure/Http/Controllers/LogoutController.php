<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LogoutUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController
{
    public function __construct(
        private LogoutUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $bearerToken = $request->bearerToken();
        $actor = $request->user();

        if ($actor === null) {
            $plainTextToken = $this->resolvePlainTextToken($bearerToken, $request->cookie('auth_token'));
            $actor = $this->resolveActorFromToken($plainTextToken);
        }

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
            $this->useCase->execute($actor, $bearerToken);

            return response()->json([
                'message' => 'Logout successful',
            ], 200)->withCookie($expiredCookie);
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 409)->withCookie($expiredCookie);
        } catch (AuthAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 401)->withCookie($expiredCookie);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500)->withCookie($expiredCookie);
        }
    }

    private function resolvePlainTextToken(?string $bearerToken, ?string $cookieToken): ?string
    {
        if (is_string($bearerToken) && trim($bearerToken) !== '') {
            return trim($bearerToken);
        }

        if (is_string($cookieToken) && trim($cookieToken) !== '') {
            return trim($cookieToken);
        }

        return null;
    }

    private function resolveActorFromToken(?string $plainTextToken): UserModel|PatientModel|null
    {
        if (!is_string($plainTextToken) || trim($plainTextToken) === '') {
            return null;
        }

        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken(trim($plainTextToken));

        if ($personalAccessToken === null) {
            return null;
        }

        return $personalAccessToken->tokenable;
    }
}
