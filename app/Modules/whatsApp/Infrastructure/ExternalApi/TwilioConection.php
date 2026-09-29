<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Infrastructure\ExternalApi;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Twilio\Rest\Client;

class TwilioConection
{
    private Client $client;

    /**
     * Credentials come from config/services.php (spec 015, RD4.a), so they survive the
     * configuration cache in production. A client can be passed in to test without network.
     */
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        );
    }

    /**
     * Envía una plantilla de WhatsApp.
     *
     * Spec 015 (CA18): the logs never carry the destination number, the template variables
     * (they include the patient's name) nor Twilio's error text, which repeats the number.
     * A failure is rethrown as an exception holding only Twilio's error code.
     *
     * @param  string  $to  Número destino (ej: whatsapp:+521...)
     * @param  string  $templateName  Nombre de la plantilla aprobada en Twilio (usado para logs)
     * @param  array<int, string>  $templateVariables  Variables de la plantilla, en orden
     */
    public function sendTemplate(string $to, string $templateName, array $templateVariables = []): void
    {
        Log::info('TwilioConection::sendTemplate iniciado', [
            'templateName' => $templateName,
            'variableCount' => count($templateVariables),
        ]);

        $phoneNumber = (string) config('services.twilio.from'); // '+1415...' o 'whatsapp:+1415...'
        $from = str_starts_with($phoneNumber, 'whatsapp:') ? $phoneNumber : 'whatsapp:'.$phoneNumber;
        $contentSid = config('services.twilio.appointment_template_sid');

        if (! $contentSid) {
            throw new RuntimeException('TWILIO_APPOINTMENT_TEMPLATE_SID is not configured');
        }

        $messageOptions = [
            'from' => $from,
            'contentSid' => $contentSid,
        ];

        // Si la plantilla tiene variables, se pasan como array de valores (no como JSON)
        if (! empty($templateVariables)) {
            $formattedVariables = [];
            foreach (array_values($templateVariables) as $index => $value) {
                $formattedVariables[(string) ($index + 1)] = (string) $value;
            }

            $messageOptions['contentVariables'] = json_encode($formattedVariables);
        }

        try {
            $response = $this->client->messages->create($to, $messageOptions);
        } catch (\Exception $e) {
            Log::error('TwilioConection::sendTemplate - ERROR', [
                'templateName' => $templateName,
                'errorCode' => $e->getCode(),
                'exception' => $e::class,
            ]);

            throw new RuntimeException('Twilio rejected the WhatsApp message (code '.$e->getCode().')', (int) $e->getCode());
        }

        Log::info('TwilioConection: Respuesta exitosa de Twilio', [
            'messageId' => $response->sid,
            'status' => $response->status,
        ]);
    }
}
