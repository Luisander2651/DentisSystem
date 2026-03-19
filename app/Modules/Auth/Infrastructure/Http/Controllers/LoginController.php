<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\LoginDTO;
use App\Modules\Auth\Aplication\Exceptions\AuthAplicationExceptions;
use App\Modules\Auth\Aplication\UseCases\LoginUseCase;
use App\Modules\Auth\Domain\Exceptions\AuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginController
{
    public function __construct(
        private LoginUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
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
            ], 200);
        } catch (AuthException $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        } catch (AuthAplicationExceptions $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
