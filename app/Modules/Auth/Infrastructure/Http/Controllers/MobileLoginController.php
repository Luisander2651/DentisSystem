<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\LoginDTO;
use App\Modules\Auth\Aplication\UseCases\MobileLoginUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use App\Modules\Auth\Domain\Exceptions\MobileAccessDeniedException;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Login for the Android attendance app: the app cannot read the httpOnly cookie the web
 * login sets, so the token travels in the body instead and no cookie is issued.
 */
final class MobileLoginController
{
    public function __construct(
        private MobileLoginUseCase $useCase,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $dto = LoginDTO::create(
                email: $request->string('email')->value(),
                password: $request->string('password')->value(),
            );

            $result = $this->useCase->execute($dto);

            return response()->json([
                'message' => 'Login successful',
                'data' => $result,
            ], 200)->header('Cache-Control', 'no-store');
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (MobileAccessDeniedException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('MobileLoginController: unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
