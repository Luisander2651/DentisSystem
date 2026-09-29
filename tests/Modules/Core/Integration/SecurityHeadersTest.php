<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA5 and CA1 (RS6.a, TM4): every response carries the security headers, the CSP
 * only lets in the site's own scripts (inline ones with a nonce) and, while vite runs in hot
 * mode, also the dev server and its websocket. X-Powered-By is removed by the production
 * image (expose_php=Off), not by the app, so it is checked against the real server (A44).
 */

function expectSecurityHeaders(TestResponse $response): void
{
    $csp = (string) $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("frame-ancestors 'none'")
        ->and($csp)->toMatch("/script-src 'self' 'nonce-[^']+'/")
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
}

it('sends the security headers on web pages', function () {
    expectSecurityHeaders($this->get('/login')->assertOk());
});

it('sends the security headers on the api', function () {
    $this->withoutRateLimiting();

    expectSecurityHeaders($this->getJson('/api/v1/public/certifications')->assertOk());
});

it('sends the security headers on the health check', function () {
    expectSecurityHeaders($this->get('/up'));
});

it('allows the inline scripts of the login page through the same nonce as the CSP', function () {
    $response = $this->get('/login')->assertOk();

    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $match);
    expect($match[1] ?? null)->not->toBeNull();

    preg_match_all('/<script(?![^>]*\bsrc=)([^>]*)>/', $response->getContent(), $inlineScripts);
    expect($inlineScripts[1])->not->toBeEmpty();
    foreach ($inlineScripts[1] as $attributes) {
        expect($attributes)->toContain('nonce="'.$match[1].'"');
    }
});

it('switches to report-only when the emergency flag is on', function () {
    config(['security.csp.report_only' => true]);

    $response = $this->get('/login')->assertOk();

    expect($response->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and((string) $response->headers->get('Content-Security-Policy-Report-Only'))->toContain("default-src 'self'");
});

it('lets the vite dev server and its websocket through while running hot (CA1)', function () {
    // Never public/hot: other parallel processes and the local setup read that file (A45).
    $hotFile = storage_path('framework/testing/hot-'.Str::uuid());
    @mkdir(dirname($hotFile), 0775, true);
    file_put_contents($hotFile, 'http://localhost:5173');
    Vite::useHotFile($hotFile);

    try {
        $csp = (string) $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');
    } finally {
        unlink($hotFile);
    }

    expect($csp)->toContain('http://localhost:5173')
        ->and($csp)->toContain('ws://localhost:5173')
        ->and(is_file(public_path('hot')))->toBeFalse();
});
