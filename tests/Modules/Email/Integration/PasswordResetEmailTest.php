<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Events\SendEmailForChangePasswordEvent;
use App\Modules\Email\Aplication\UseCases\SendResetPasswordEmailUseCase;
use App\Modules\Email\Infrastructure\ExternalApi\BrevoApi;
use App\Modules\Email\Infrastructure\Listeners\SendPasswordResetListener;
use Brevo\Brevo;
use Brevo\Exceptions\BrevoApiException;
use Brevo\TransactionalEmails\Requests\SendTransacEmailRequest;
use Brevo\TransactionalEmails\TransactionalEmailsClient;
use Brevo\TransactionalEmails\Types\SendTransacEmailResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Modules\Email\Integration\EmailIntegrationTestCase;

uses(EmailIntegrationTestCase::class);

/*
 * Spec 015:
 * - CA18 (OB2.b, RS9.a, A53): from the controller that receives the request to Brevo, no log
 *   carries the e-mail, the name, the provider's body or traces; the trace the worker reports
 *   only names files and lines (A54, A61).
 * - CA19: the reset link points to the configured site, not to localhost.
 * The real BrevoApi runs with a stubbed Brevo client, swapped in only here through the same
 * contextual binding AppServiceProvider registers (A57).
 */

beforeEach(function () {
    // Without an injectable client the flow would call the real Brevo API.
    $parameters = array_map(
        fn (ReflectionParameter $parameter): string => $parameter->getName(),
        (new ReflectionClass(BrevoApi::class))->getConstructor()?->getParameters() ?? [],
    );
    expect(in_array('client', $parameters, true))->toBeTrue('BrevoApi does not accept a Brevo client yet');

    config(['app.url' => 'https://dentissapp.com']);
    $this->withoutRateLimiting();

    $this->logFile = storage_path('logs/reset-flow-'.Str::uuid().'.log');
    config(['logging.channels.flow_capture' => ['driver' => 'single', 'path' => $this->logFile, 'level' => 'debug']]);
    Log::setDefaultDriver('flow_capture');

    $this->sentRequest = null;

    // Swaps the Brevo client of the real BrevoApi the use case receives.
    $this->useBrevoClient = function (callable $send): void {
        $emails = Mockery::mock(TransactionalEmailsClient::class);
        $emails->shouldReceive('sendTransacEmail')->andReturnUsing($send);
        $client = new class($emails) extends Brevo
        {
            public function __construct(TransactionalEmailsClient $emails)
            {
                $this->transactionalEmails = $emails;
            }
        };

        $this->app->when(SendResetPasswordEmailUseCase::class)
            ->needs(BrevoApi::class)
            ->give(fn () => new BrevoApi(TemplateId: 7, apiKey: 'test-key', client: $client));
    };

    $this->brevoAccepting = function (): void {
        ($this->useBrevoClient)(function (SendTransacEmailRequest $request): SendTransacEmailResponse {
            $this->sentRequest = $request;

            return new SendTransacEmailResponse(['messageId' => '<test@brevo>']);
        });
    };

    $this->brevoRejecting = function (): void {
        ($this->useBrevoClient)(function (SendTransacEmailRequest $request): never {
            $email = $request->to[0]->email ?? 'unknown';

            throw new BrevoApiException("Invalid recipient {$email}", 400, ['message' => "email {$email} is blocked"]);
        });
    };

    $this->patientWithEmail = fn () => $this->createPatient([
        'first_name' => 'Rosaura',
        'last_name' => 'Pruebamail',
        'email' => 'rosaura.pruebamail@example.com',
    ]);
});

afterEach(function () {
    if (isset($this->logFile) && is_file($this->logFile)) {
        unlink($this->logFile);
    }
});

function expectNoResetPersonalData(string $text): void
{
    foreach (['rosaura.pruebamail@example.com', 'Rosaura', 'Pruebamail'] as $value) {
        expect($text)->not->toContain($value);
    }
}

it('sends the reset e-mail with a link to the configured site (CA19)', function () {
    ($this->brevoAccepting)();
    ($this->patientWithEmail)();

    $this->postJson($this->sendResetUrl(), ['email' => 'rosaura.pruebamail@example.com'])->assertOk();

    expect($this->sentRequest)->not->toBeNull()
        ->and($this->sentRequest->params['reset_url'] ?? '')->toStartWith('https://dentissapp.com/reset-password?token=');
});

it('logs a successful reset e-mail without the e-mail address or the name', function () {
    ($this->brevoAccepting)();
    ($this->patientWithEmail)();

    $this->postJson($this->sendResetUrl(), ['email' => 'rosaura.pruebamail@example.com'])->assertOk();

    $log = (string) @file_get_contents($this->logFile);
    expectNoResetPersonalData($log);
    expect($log)->not->toContain('#0 ');
});

it('logs a reset e-mail rejected by Brevo without personal data, provider body or traces', function () {
    ($this->brevoRejecting)();
    ($this->patientWithEmail)();

    $this->postJson($this->sendResetUrl(), ['email' => 'rosaura.pruebamail@example.com']);

    $log = (string) @file_get_contents($this->logFile);
    // The rejection itself must have happened and been logged, or the test proves nothing.
    expect($log)->toContain('Brevo API rejected the transactional email request');
    expectNoResetPersonalData($log);
    expect($log)->not->toContain('is blocked')
        ->and($log)->not->toContain('#0 ');
});

it('keeps personal data out of the exception and of the trace the worker reports (A54, A61)', function () {
    $event = null;
    Event::listen(SendEmailForChangePasswordEvent::class, function (SendEmailForChangePasswordEvent $dispatched) use (&$event) {
        $event = $dispatched;
    });
    ($this->brevoAccepting)();
    ($this->patientWithEmail)();
    $this->postJson($this->sendResetUrl(), ['email' => 'rosaura.pruebamail@example.com'])->assertOk();
    expect($event)->not->toBeNull();

    // The worker runs outside any HTTP request and the production image ignores trace args.
    ($this->brevoRejecting)();
    $this->app->instance('request', Request::create('/'));
    $previous = ini_set('zend.exception_ignore_args', '1');
    file_put_contents($this->logFile, '');

    $thrown = null;
    try {
        app(SendPasswordResetListener::class)->handle($event);
    } catch (Throwable $e) {
        $thrown = $e;
        report($e);
    } finally {
        ini_set('zend.exception_ignore_args', (string) $previous);
    }

    expect($thrown)->not->toBeNull();
    expectNoResetPersonalData($thrown->getMessage());
    expect($thrown->getMessage())->not->toContain('is blocked');

    $log = (string) file_get_contents($this->logFile);
    expect($log)->not->toBeEmpty();
    expectNoResetPersonalData($log);
    expect($log)->not->toContain('is blocked');
});
