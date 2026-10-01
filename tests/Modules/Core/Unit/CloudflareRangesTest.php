<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

/*
 * Spec 015, CA16 (D13): the Cloudflare ranges live in two places - Laravel's trusted proxies
 * and nginx's real_ip - and both must be the same list, or the IP seen by the app and the one
 * written by nginx drift apart.
 */

it('trusts exactly the same Cloudflare ranges in Laravel and in the production nginx', function () {
    $laravelRanges = config('security.trusted_proxies');

    $nginxConfig = base_path('docker/nginx/prod.conf');
    expect(is_file($nginxConfig))->toBeTrue('docker/nginx/prod.conf does not exist');

    preg_match_all('/^\s*set_real_ip_from\s+([^;\s]+)\s*;/m', (string) file_get_contents($nginxConfig), $matches);
    $nginxRanges = $matches[1];

    expect($laravelRanges)->toBeArray()->not->toBeEmpty()
        ->and($nginxRanges)->not->toBeEmpty();

    sort($laravelRanges);
    sort($nginxRanges);

    expect($nginxRanges)->toBe($laravelRanges);
});
