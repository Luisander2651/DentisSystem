<?php

declare(strict_types=1);

namespace App\Modules\whatsApp\Infrastructure\ExternalApi;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TwilioConection
{
    private Client $client;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $authToken = env('TWILIO_AUTH_TOKEN');
        $this->client = new Client($sid, $authToken);
    }

    /**
     * Envía una plantilla de WhatsApp.
     * 
     * @param string $to Número destino (ej: whatsapp:+521...)
     * @param string $templateName Nombre de la plantilla aprobada en Twilio (usado para logs)
     * @param array $templateVariables Variables para reemplazar en la plantilla como array indexado, ej: ['Juan Pérez', '2026-04-30', '14:30']
     */
    public function sendTemplate(string $to, string $templateName, array $templateVariables = []): void
    {
        Log::info('TwilioConection::sendTemplate iniciado', [
            'to' => $to,
            'templateName' => $templateName,
            'variablesCount' => count($templateVariables),
        ]);

        try {
            $phonNumber = env('TWILIO_PHONE_NUMBER'); // Puede ser '+1415...' o 'whatsapp:+1415...'
            // Asegurar que tenga el prefijo whatsapp:
            $from = strpos($phonNumber, 'whatsapp:') === 0 ? $phonNumber : 'whatsapp:' . $phonNumber;
            $contentSid = env('TWILIO_APPOINTMENT_TEMPLATE_SID'); // SID de la plantilla aprobada
            
            Log::info('TwilioConection: Configuración', [
                'from' => $from,
                'contentSid' => $contentSid ? 'set' : 'NOT SET',
                'sid' => env('TWILIO_SID') ? 'set' : 'NOT SET',
                'authToken' => env('TWILIO_AUTH_TOKEN') ? 'set' : 'NOT SET',
            ]);

            if (!$contentSid) {
                throw new \Exception('TWILIO_APPOINTMENT_TEMPLATE_SID no está configurado en .env');
            }

            $messageOptions = [
                'from' => $from,
                'contentSid' => $contentSid,
            ];

            // Si la plantilla tiene variables, se pasan como array de valores (no como JSON)
            if (!empty($templateVariables)) {
                $formattedVariables = [];
                foreach ($templateVariables as $index => $value) {
                    $key = (string)($index + 1);
                    $formattedVariables[$key] = (string)$value;
                }

                $messageOptions['contentVariables'] = json_encode($formattedVariables);
    
                Log::info('TwilioConection: Variables JSON preparada', [
                    'json' => $messageOptions['contentVariables']
                ]);
            }

            Log::info('TwilioConection: Enviando mensaje a Twilio API', [
                'to' => $to,
                'contentSid' => $contentSid,
                'hasVariables' => !empty($templateVariables),
                'variableCount' => count($templateVariables),
            ]);

            $response = $this->client->messages->create($to, $messageOptions);

            Log::info('TwilioConection: Respuesta exitosa de Twilio', [
                'messageId' => $response->sid,
                'status' => $response->status,
            ]);
        } catch (\Exception $e) {
            Log::error('TwilioConection::sendTemplate - ERROR', [
                'error' => $e->getMessage(),
                'errorCode' => $e->getCode(),
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
