<?php

declare(strict_types=1);

namespace App\Modules\Email\Aplication\UseCases;

use App\Modules\Email\Infrastructure\ExternalApi\BrevoApi;

// El usecase se va a encargar de generar los amgic codes
// Se encargara de crearlos en la base de datos
// Se encargara de enviar el email al cliente con el magic codelink
final readonly class SendResetPasswordEmailUseCase
{

    private const URL_RESET_PASSWORD = 'http://localhost:8000/reset-password?token='; // El token expirará en 60 minutos
    public function __construct(
        private BrevoApi $brevoApi,
    ) {}

    public function execute(string $email, string $name, string $token): void
    {
        $resetUrl = self::URL_RESET_PASSWORD . $token;
        $this->brevoApi->sendEmail(
            email: $email,
            name: $name,
            params: [
                'nombre' => $name,
                'email' => $email,
                'reset_url' => $resetUrl,
            ]
        );
    }
}