<?php

namespace App\Modules\Email\Infrastructure\ExternalApi;

use Brevo\Brevo;
use Brevo\Exceptions\BrevoApiException;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestSender;
use Brevo\TransactionalEmails\Types\SendTransacEmailRequestToItem;
use Brevo\TransactionalEmails\Types\SendTransacEmailResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Spec 015: the API key comes from config('services.brevo.api_key') through
 * AppServiceProvider (RD4.a); a Brevo client can be passed in to test without network.
 * Logs and rethrown exceptions carry only the status code and the template id, never the
 * recipient, Brevo's body or its message, which repeat the e-mail address (CA18).
 */
final readonly class BrevoApi
{
    private Brevo $client;

    public function __construct(
        public int $TemplateId,
        public string $apiKey = '',
        ?Brevo $client = null,
    ) {
        $this->client = $client ?? new Brevo(apiKey: $apiKey);
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
                'templateId' => $this->TemplateId,
            ]);

            throw new RuntimeException('Brevo rejected the email (status '.$e->getCode().')', (int) $e->getCode());
        } catch (\Exception $e) {
            Log::error('Brevo email request failed', [
                'exception' => $e::class,
                'templateId' => $this->TemplateId,
            ]);

            throw new RuntimeException('Brevo email request failed');
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
