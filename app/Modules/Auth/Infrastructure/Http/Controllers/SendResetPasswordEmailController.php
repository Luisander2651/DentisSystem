<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\Dtos\SendEmailForChangePasswordDTO;
use App\Modules\Auth\Aplication\UseCases\SendEmailForChangePasswordUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SendResetPasswordEmailController
{
    public function __construct(
        private SendEmailForChangePasswordUseCase $useCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $dto = new SendEmailForChangePasswordDTO(
                email: $request->input('email'),
            );
            $this->useCase->execute($dto);
            return new JsonResponse(['message' => 'Email de restablecimiento de contraseña enviado.']);
        } catch (\Exception $e) {
            Log::error('SendResetPasswordEmailController: Error al enviar email de restablecimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Error al enviar el email de restablecimiento de contraseña.'], 500);
        }
    }
}