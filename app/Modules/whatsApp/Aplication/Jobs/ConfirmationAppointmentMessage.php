<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Aplication\Jobs;

use App\Modules\whatsApp\Infrastructure\ExternalApi\TwilioConection;
use Illuminate\Support\Facades\Log;

class ConfirmationAppointmentMessage
{
    private TwilioConection $twilio;
    private string $templateName = 'appointment_scheduled';

    public function __construct()
    {
        $this->twilio = new TwilioConection();
    }

    public function handle(string $to, array $templateVariables = []): void
    {
        Log::info('ConfirmationAppointmentMessage: Preparando envío', [
            'to' => $to,
            'template' => $this->templateName,
        ]);

        try {
            $this->twilio->sendTemplate($to, $this->templateName, $templateVariables);
            Log::info('ConfirmationAppointmentMessage: Envío completado');
        } catch (\Exception $e) {
            Log::error('ConfirmationAppointmentMessage: Error en sendTemplate', [
                'error' => $e->getMessage(),
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}