<?php

declare(strict_types=1);

use App\Modules\Email\Aplication\UseCases\SendResetPasswordEmailUseCase;
use App\Modules\whatsApp\Infrastructure\ExternalApi\TwilioConection;
use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, CA3 (RD4.a): the Twilio and Brevo adapters take their credentials from config(),
 * so they keep working with the configuration cached in production. The real adapters are
 * built without any network call.
 */

function privateProperty(object $object, string $name): mixed
{
    $reflection = new ReflectionObject($object);
    while (! $reflection->hasProperty($name) && $reflection->getParentClass() !== false) {
        $reflection = $reflection->getParentClass();
    }

    return $reflection->getProperty($name)->getValue($object);
}

it('builds the Twilio client with the credentials from config', function () {
    config([
        'services.twilio.sid' => 'AC-from-config',
        'services.twilio.token' => 'token-from-config',
    ]);

    $client = privateProperty(new TwilioConection, 'client');

    expect($client->getUsername())->toBe('AC-from-config')
        ->and($client->getPassword())->toBe('token-from-config');
});

it('gives the reset use case a BrevoApi with the API key from config', function () {
    config(['services.brevo.api_key' => 'brevo-key-from-config']);

    $brevoApi = privateProperty(app(SendResetPasswordEmailUseCase::class), 'brevoApi');

    expect($brevoApi->apiKey ?? null)->toBe('brevo-key-from-config');
});
