<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Aplication\Jobs;

use App\Modules\whatsApp\Infrastructure\ExternalApi\TwilioConection;
use Illuminate\Support\Facades\Log;

/**
 * Spec 015 (CA18): logs name the template, never the destination number.
 */
class ConfirmationAppointmentMessage
{
    private string $templateName = 'appointment_scheduled';

    public function __construct(
        private TwilioConection $twilio
    ) {}

    public function handle(string $to, array $templateVariables = []): void
    {
        Log::info('ConfirmationAppointmentMessage: Preparando envío', [
            'template' => $this->templateName,
        ]);

        try {
            $this->twilio->sendTemplate($to, $this->templateName, $templateVariables);
            Log::info('ConfirmationAppointmentMessage: Envío completado');
        } catch (\Exception $e) {
            Log::error('ConfirmationAppointmentMessage: Error en sendTemplate', [
                'template' => $this->templateName,
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);
            throw $e;
        }
    }
}
