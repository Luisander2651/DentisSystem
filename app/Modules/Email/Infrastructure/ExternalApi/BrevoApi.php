<?php

namespace App\Modules\Email\Infrastructure\ExternalApi;

use Brevo\Brevo;
use Brevo\Exceptions\BrevoApiException;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use \Brevo\TransactionalEmails\Types\SendTransacEmailResponse;
use Illuminate\Support\Facades\Log;

final readonly class BrevoApi
{
    private Brevo $client;

    public function __construct(
        public int $TemplateId,
    )
    {
        $this->client = new Brevo(apiKey: env('BREVO_EMAIL_SENDER_API_KEY'));
    }

    public function sendEmail(string $email, string $name, array $params = []): SendTransacEmailResponse
    {
        $request = $this->createSendTransacEmailRequest(
            subject: null,
            templateId: $this->TemplateId,
            sender: null,
            to: [
                new SendTransacEmailRequestToItem([
                    'email' => $email,
                    'name' => $name,
                ]),
            ],
            params: $params
        );
        try {
            return $this->client->transactionalEmails->sendTransacEmail($request);
        } catch (BrevoApiException $e) {
            Log::error('Brevo API rejected the transactional email request', [
                'statusCode' => $e->getCode(),
                'body' => $e->getBody(),
                'templateId' => $this->TemplateId,
            ]);
            throw new \RuntimeException('Error al enviar el correo electrónico: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            // Manejo de errores, puedes loguear el error o lanzar una excepción personalizada
            Log::error('Error al enviar el correo electrónico: ' . $e->getMessage());
            throw new \RuntimeException('Error al enviar el correo electrónico: ' . $e->getMessage(), 0, $e);
        }
    }
    
    private function createSendTransacEmailRequest(
        ?string $subject,
        int $templateId,
        ?SendTransacEmailRequestSender $sender,
        array $to,
        array $params = []
    ): SendTransacEmailRequest {
        return new SendTransacEmailRequest([
            'subject' => $subject,
            'templateId' => $templateId,
            'sender' => $sender,
            'to' => $to,
            'params' => $params,
        ]);
    }
}