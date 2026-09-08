<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\ResetPasswordDTO;
use App\Modules\Auth\Aplication\UseCases\ResetPasswordUseCase;
use App\Modules\Auth\Domain\Exceptions\PasswordResetServiceException;
use App\Modules\Auth\Infrastructure\Exceptions\Repositories\PasswordResetException;
use App\Modules\Auth\Infrastructure\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

final readonly class ResetPasswordController
{
    public function __construct(
        private ResetPasswordUseCase $resetPasswordUseCase,
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $dto = new ResetPasswordDTO(
                token: $request->string('token')->value(),
                newPassword: $request->string('new_password')->value(),
            );

            $this->resetPasswordUseCase->execute($dto);

            return response()->json([
                'message' => 'Password reset successful',
            ], 200);
        } catch (PasswordResetServiceException|PasswordResetException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ResetPasswordController: unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
