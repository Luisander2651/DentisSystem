<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\UseCases\ResetPasswordUseCase;
use App\Modules\Auth\Aplication\DTOs\ResetPasswordDTO;
use App\Modules\Auth\Domain\Exceptions\PasswordResetServiceException;
use App\Modules\Auth\Infrastructure\Exceptions\Repositories\PasswordResetException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final readonly class ResetPasswordController
{
    public function __construct(
        private ResetPasswordUseCase $resetPasswordUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new ResetPasswordDTO(
                token: (string) $request->query('token'),
                newPassword: (string) $request->input('new_password')
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
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}