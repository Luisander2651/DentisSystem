<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Aplication\DTOs\SendEmailForChangePasswordDTO;
use App\Modules\Auth\Aplication\UseCases\SendEmailForChangePasswordUseCase;
use App\Modules\Auth\Infrastructure\Http\Requests\SendResetPasswordEmailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final class SendResetPasswordEmailController
{
    public function __construct(
        private SendEmailForChangePasswordUseCase $useCase,
    ) {}

    public function __invoke(SendResetPasswordEmailRequest $request): JsonResponse
    {
        try {
            $dto = new SendEmailForChangePasswordDTO(
                email: $request->string('email')->value(),
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
