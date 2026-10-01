<?php

declare(strict_types=1);

use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA6 (RS6.b, TM5): only the site's own origin may read the API from a browser,
 * and the API never tells a browser to send credentials cross-origin.
 */

beforeEach(function () {
    $this->withoutRateLimiting();
    $this->siteOrigin = rtrim((string) config('app.url'), '/');
});

function preflight($test, string $origin)
{
    return $test->call('OPTIONS', '/api/v1/public/certifications', [], [], [], [
        'HTTP_ORIGIN' => $origin,
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
    ]);
}

it('does not allow another origin to read the API (abuse)', function () {
    // A browser only exposes the response if Access-Control-Allow-Origin is '*' or the
    // requesting origin itself; any other value (or none) blocks it.
    $allowedFor = fn ($response): ?string => $response->headers->get('Access-Control-Allow-Origin');

    foreach ([preflight($this, 'https://evil.example'), $this->getJson('/api/v1/public/certifications', ['Origin' => 'https://evil.example'])] as $response) {
        expect($allowedFor($response))->not->toBe('*')
            ->and($allowedFor($response))->not->toBe('https://evil.example');
    }
});

it('allows the site own origin', function () {
    $response = preflight($this, $this->siteOrigin);

    expect($response->headers->get('Access-Control-Allow-Origin'))->toBe($this->siteOrigin);
});

it('never allows credentials cross-origin', function () {
    foreach ([$this->siteOrigin, 'https://evil.example'] as $origin) {
        expect(preflight($this, $origin)->headers->get('Access-Control-Allow-Credentials'))->not->toBe('true');
    }
});
