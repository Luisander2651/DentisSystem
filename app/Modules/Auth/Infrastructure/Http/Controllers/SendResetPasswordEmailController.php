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
            // Spec 015 (CA18): the message and the trace may carry the e-mail address.
            Log::error('SendResetPasswordEmailController: Error al enviar email de restablecimiento', [
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);

            return new JsonResponse(['error' => 'Error al enviar el email de restablecimiento de contraseña.'], 500);
        }
    }
}
