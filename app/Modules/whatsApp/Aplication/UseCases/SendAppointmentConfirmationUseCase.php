<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Aplication\UseCases;

use App\Modules\whatsApp\Aplication\DTOs\SendConfirmationAppointmentMessageDTO;
use App\Modules\whatsApp\Aplication\Jobs\ConfirmationAppointmentMessage;
use Illuminate\Support\Facades\Log;

/**
 * Spec 015 (CA18): logs never carry the destination number nor the template variables,
 * which include the patient's name.
 */
final readonly class SendAppointmentConfirmationUseCase
{
    public function __construct(
        private ConfirmationAppointmentMessage $confirmationMessageJob
    ) {}

    public function execute(SendConfirmationAppointmentMessageDTO $dto): void
    {
        $to = 'whatsapp:'.$dto->customerPhone;
        // Twilio espera las variables como array de valores en orden, sin claves
        $templateVariables = [
            $dto->customerName,
            $dto->date,
            $dto->time,
        ];

        Log::info('SendAppointmentConfirmationUseCase: Enviando a Twilio', [
            'variableCount' => count($templateVariables),
        ]);

        try {
            $this->confirmationMessageJob->handle($to, $templateVariables);

            Log::info('SendAppointmentConfirmationUseCase: Mensaje enviado exitosamente');
        } catch (\Exception $e) {
            Log::error('SendAppointmentConfirmationUseCase: Error al enviar confirmación', [
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }
}
