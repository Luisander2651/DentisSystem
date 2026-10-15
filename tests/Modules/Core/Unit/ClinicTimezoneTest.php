<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 017, RNF: "now" is the clinic's date and time in Mexico City. The zone rules the whole
 * application: the appointments rules, today's appointments (007) and the validity of the
 * promotions (011, 012), whose `new DateTime()` takes PHP's default zone.
 */

it('uses the clinic timezone as the application timezone', function () {
    expect(config('app.timezone'))->toBe('America/Mexico_City');
});

it('defaults to the clinic timezone when APP_TIMEZONE is not set', function () {
    $saved = [getenv('APP_TIMEZONE'), $_ENV['APP_TIMEZONE'] ?? null, $_SERVER['APP_TIMEZONE'] ?? null];
    putenv('APP_TIMEZONE');
    unset($_ENV['APP_TIMEZONE'], $_SERVER['APP_TIMEZONE']);

    try {
        $timezone = (require base_path('config/app.php'))['timezone'];
    } finally {
        if ($saved[0] !== false) {
            putenv('APP_TIMEZONE='.$saved[0]);
        }
        if ($saved[1] !== null) {
            $_ENV['APP_TIMEZONE'] = $saved[1];
        }
        if ($saved[2] !== null) {
            $_SERVER['APP_TIMEZONE'] = $saved[2];
        }
    }

    expect($timezone)->toBe('America/Mexico_City');
});

it('sets the clinic timezone as the PHP default timezone', function () {
    expect(date_default_timezone_get())->toBe('America/Mexico_City');
});
