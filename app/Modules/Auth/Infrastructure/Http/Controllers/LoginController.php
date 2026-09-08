<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\LoginDTO;
use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LoginUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class LoginController
{
    public function __construct(
        private LoginUseCase $useCase,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginDTO::create(
                email: $request->string('email')->value(),
                password: $request->string('password')->value(),
            );

            $result = $this->useCase->execute($dto);

            $useSecureCookie = $request->isSecure();

            $cookie = cookie(
                name: 'auth_token',
                value: $result['token'],
                minutes: (int) config('sanctum.expiration', 1440),
                path: (string) config('session.path', '/'),
                domain: config('session.domain'),
                secure: $useSecureCookie,
                httpOnly: true,
                raw: false,
                sameSite: (string) config('session.same_site', 'lax'),
            );

            unset($result['token']);

            return response()->json([
                'message' => 'Login successful',
                'data' => $result,
            ], 200)->withCookie($cookie);
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (AuthAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            Log::error('LoginController: unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
