<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Events\ScheduledAppointment;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\ContactInfoModel;
use App\Modules\whatsApp\Infrastructure\ExternalApi\TwilioConection;
use App\Modules\whatsApp\Infrastructure\Listeners\CreatedAppointmentListener;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 015, CA18 (OB2.b, RS9.a, OB10.b whatsApp): from the appointment request to Twilio, no
 * log carries the patient's phone, name or template variables, nor the provider's message.
 * The application logs carry no traces; the trace that Laravel writes when the worker reports
 * a failed job only names files and lines (A61). Logs are read from a real file written with
 * Laravel's own formatter, which is what reaches Loki in production.
 */

beforeEach(function () {
    // Without an injectable client the flow would call the real Twilio API.
    $constructor = (new ReflectionClass(TwilioConection::class))->getConstructor();
    expect($constructor?->getNumberOfParameters() ?? 0)
        ->toBeGreaterThan(0, 'TwilioConection does not accept a Twilio client yet');

    // Fictitious settings: the flow must not depend on the Twilio values of a local .env.
    config([
        'services.twilio.sid' => 'ACtest',
        'services.twilio.token' => 'test-token',
        'services.twilio.from' => '+14155550100',
        'services.twilio.appointment_template_sid' => 'HXtest',
    ]);

    $this->logFile = storage_path('logs/whatsapp-flow-'.Str::uuid().'.log');
    config(['logging.channels.flow_capture' => ['driver' => 'single', 'path' => $this->logFile, 'level' => 'debug']]);
    Log::setDefaultDriver('flow_capture');

    // Bound to the test case, so it can use its protected helpers.
    $this->bookAppointmentForPatientWithPhone = function (): array {
        $patient = $this->createPatient(['first_name' => 'Zacarias', 'last_name' => 'Pruebatel']);
        ContactInfoModel::create([
            'patient_id' => $patient->id,
            'phone_number' => '+52 555 010 9876',
            'email' => 'zacarias.contacto@example.com',
            'emergency_contact' => 'Jane Doe',
        ]);

        $this->actingAsAdmin();
        $this->postJson($this->appointmentsUrl(), $this->validCreateAppointmentPayload(['patient_id' => $patient->id]));

        // Everything the flow knows about the patient and the template variables: phone,
        // name, contact e-mail, emergency contact and the date and time of the appointment.
        return [
            '555 010 9876', '5550109876', 'Zacarias', 'Pruebatel',
            'zacarias.contacto@example.com', 'Jane Doe', '2026-09-01', '10:00',
        ];
    };
});

afterEach(function () {
    if (isset($this->logFile) && is_file($this->logFile)) {
        unlink($this->logFile);
    }
});

/**
 * A Twilio client whose messages->create() answers with $create, so nothing leaves the test.
 */
function twilioClientStub(callable $create): Client
{
    $messages = new class($create)
    {
        public function __construct(private $create) {}

        public function create(string $to, array $options): object
        {
            return ($this->create)($to, $options);
        }
    };

    return new class($messages) extends Client
    {
        public function __construct(public $messages) {}
    };
}

function twilioRejectingTheNumber(): Client
{
    return twilioClientStub(function (string $to): never {
        throw new RestException("The 'To' number {$to} is not a valid phone number.", 21211, 400);
    });
}

function expectNoPersonalData(string $text, array $personalData): void
{
    // Without the timestamp of each line, which could contain the appointment's time by chance.
    $text = (string) preg_replace('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] /m', '', $text);

    foreach ($personalData as $value) {
        expect($text)->not->toContain($value);
    }
}

it('logs a successful WhatsApp confirmation without the patient phone, name or template variables', function () {
    $this->app->instance(Client::class, twilioClientStub(fn (): object => (object) ['sid' => 'SM123', 'status' => 'queued']));

    $personalData = ($this->bookAppointmentForPatientWithPhone)();

    $log = (string) @file_get_contents($this->logFile);
    expect($log)->toContain('CreatedAppointmentListener');
    expectNoPersonalData($log, $personalData);
    expect($log)->not->toContain('#0 ');
});

it('logs a WhatsApp confirmation rejected by Twilio without personal data, provider message or traces', function () {
    $this->app->instance(Client::class, twilioRejectingTheNumber());

    $personalData = ($this->bookAppointmentForPatientWithPhone)();

    $log = (string) @file_get_contents($this->logFile);
    // The rejection itself must have happened and been logged, or the test proves nothing.
    expect($log)->toContain('TwilioConection::sendTemplate - ERROR');
    expectNoPersonalData($log, $personalData);
    expect($log)->not->toContain('is not a valid phone number')
        ->and($log)->not->toContain('#0 ');
});

it('keeps personal data out of the exception and of the trace the worker reports (A54, A61)', function () {
    $scheduled = null;
    Event::listen(ScheduledAppointment::class, function (ScheduledAppointment $event) use (&$scheduled) {
        $scheduled = $event;
    });
    $this->app->instance(Client::class, twilioClientStub(fn (): object => (object) ['sid' => 'SM123', 'status' => 'queued']));
    $personalData = ($this->bookAppointmentForPatientWithPhone)();
    expect($scheduled)->not->toBeNull();

    // The worker runs outside any HTTP request and the production image ignores trace args.
    $this->app->instance(Client::class, twilioRejectingTheNumber());
    $this->app->instance('request', Request::create('/'));
    $previous = ini_set('zend.exception_ignore_args', '1');
    file_put_contents($this->logFile, '');

    $thrown = null;
    try {
        app(CreatedAppointmentListener::class)->handle($scheduled);
    } catch (Throwable $e) {
        $thrown = $e;
        report($e);
    } finally {
        ini_set('zend.exception_ignore_args', (string) $previous);
    }

    expect($thrown)->not->toBeNull();
    expectNoPersonalData($thrown->getMessage(), $personalData);
    expect($thrown->getMessage())->not->toContain('is not a valid phone number');

    $log = (string) file_get_contents($this->logFile);
    expect($log)->not->toBeEmpty();
    expectNoPersonalData($log, $personalData);
    expect($log)->not->toContain('is not a valid phone number');
});
