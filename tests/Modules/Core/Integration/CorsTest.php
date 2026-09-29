<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA6 (RS6.b, TM5): only the site's own origin may read the API from a browser,
 * and the API never tells a browser to send credentials cross-origin.
 */

beforeEach(function () {
    config(['app.url' => 'https://dentissapp.com']);
    $this->withoutRateLimiting();
});

function preflight($test, string $origin)
{
    return $test->call('OPTIONS', '/api/v1/public/certifications', [], [], [], [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);
}

it('does not allow another origin to read the API (abuse)', function () {
    $response = preflight($this, 'https://evil.example');

    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();

    $response = $this->getJson('/api/v1/public/certifications', ['Origin' => 'https://evil.example']);

    expect($response->headers->has('Access-Control-Allow-Origin'))->toBeFalse();
});

it('allows the site own origin', function () {
    $response = preflight($this, 'https://dentissapp.com');

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe('https://dentissapp.com');
});

it('never allows credentials cross-origin', function () {
    foreach (['https://dentissapp.com', 'https://evil.example'] as $origin) {
        expect(preflight($this, $origin)->headers->get('Access-Control-Allow-Credentials'))->not->toBe('true');
    }
});
