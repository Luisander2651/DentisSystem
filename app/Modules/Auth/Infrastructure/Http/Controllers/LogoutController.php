<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LogoutUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController
{
    public function __construct(
        private LogoutUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $expiredCookie = cookie()->forget(
            name: 'auth_token',
            path: (string) config('session.path', '/'),
            domain: config('session.domain'),
        );

        try {
            $this->useCase->execute($request->user());

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
}
