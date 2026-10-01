<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, CA8 and CA7 (OB4.a, OB5.a, OB5.b, RS9.b, RS10.a, RS10.b, RD5.a, RD5.b): a new
 * environment created from .env.example starts with safe defaults and the real stack.
 */

/**
 * @return array<string, string>
 */
function envExample(): array
{
    $values = [];
    foreach (file(base_path('.env.example'), FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*([A-Z0-9_]+)=(.*)$/', $line, $match)) {
            $values[$match[1]] = trim($match[2], " \t\"'");
        }
    }

    return $values;
}

it('ships safe defaults and the real stack in .env.example', function (string $name, string $expected) {
    expect(envExample())->toHaveKey($name)
        ->and(envExample()[$name])->toBe($expected);
})->with([
    'debug off' => ['APP_DEBUG', 'false'],
    'logs to stderr (Loki)' => ['LOG_CHANNEL', 'stderr'],
    'log level info' => ['LOG_LEVEL', 'info'],
    'sessions in redis' => ['SESSION_DRIVER', 'redis'],
    'encrypted sessions' => ['SESSION_ENCRYPT', 'true'],
    'cache in redis' => ['CACHE_STORE', 'redis'],
    'queue in redis' => ['QUEUE_CONNECTION', 'redis'],
    'postgresql' => ['DB_CONNECTION', 'pgsql'],
    'predis client' => ['REDIS_CLIENT', 'predis'],
    'no literal null redis password' => ['REDIS_PASSWORD', ''],
]);

it('declares by name every variable the deployment needs', function (string $name) {
    expect(envExample())->toHaveKey($name);
})->with([
    'TWILIO_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
    'TWILIO_APPOINTMENT_TEMPLATE_SID',
    'BREVO_EMAIL_SENDER_API_KEY',
    'BREVO_RESET_PASSWORD_TEMPLATE_ID',
    'APP_VERSION',
    'CSP_REPORT_ONLY',
]);
