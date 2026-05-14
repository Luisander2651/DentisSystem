<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Aplication\UseCases;

use App\Modules\whatsApp\Aplication\DTOs\SendConfirmationAppointmentMessageDTO;
use App\Modules\whatsApp\Aplication\Jobs\ConfirmationAppointmentMessage;
use Illuminate\Support\Facades\Log;

final readonly class SendAppointmentConfirmationUseCase
{
    public function __construct(
        private ConfirmationAppointmentMessage $confirmationMessageJob
    )
    {}
    public function execute(SendConfirmationAppointmentMessageDTO $dto): void
    {
        Log::info('SendAppointmentConfirmationUseCase: Iniciando envío de confirmación', [
            'customerPhone' => $dto->customerPhone,
            'customerName' => $dto->customerName,
        ]);

        try {
            $to = 'whatsapp:' . $dto->customerPhone;
            // Twilio espera las variables como array de valores en orden, sin claves
            $templateVariables = [
                $dto->customerName,
                $dto->date,
                $dto->time,
            ];

            Log::info('SendAppointmentConfirmationUseCase: Enviando a Twilio', [
                'to' => $to,
                'variables' => $templateVariables,
            ]);

            // Dispatch del job para enviar el mensaje de confirmación
            $this->confirmationMessageJob->handle($to, $templateVariables);

            Log::info('SendAppointmentConfirmationUseCase: Mensaje enviado exitosamente');
        } catch (\Exception $e) {
            Log::error('SendAppointmentConfirmationUseCase: Error al enviar confirmación', [
                'error' => $e->getMessage(),
                'customerPhone' => $dto->customerPhone,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}