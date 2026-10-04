<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 015, CA16 and CA17: behind Cloudflare the app must see the visitor's IP, but only
 * when the connection really comes from Cloudflare. 173.245.48.0/20 is one of the ranges
 * Cloudflare publishes; 198.51.100.0/24 is a documentation range outside it.
 */

beforeEach(function () {
    Route::get('/_test/client-ip', fn (Request $request) => response()->json(['ip' => $request->ip()]));
});

function publicCertificationsUrl(): string
{
    return '/api/v1/public/certifications';
}

it('uses the forwarded visitor IP when the connection comes from Cloudflare', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->getJson('/_test/client-ip', ['X-Forwarded-For' => '203.0.113.7'])
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.7']);
});

it('does not make two visitors behind the same Cloudflare node share the api limit', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
            ->getJson(publicCertificationsUrl(), ['X-Forwarded-For' => '203.0.113.7'])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->getJson(publicCertificationsUrl(), ['X-Forwarded-For' => '203.0.113.8'])
        ->assertOk();

    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->getJson(publicCertificationsUrl(), ['X-Forwarded-For' => '203.0.113.7'])
        ->assertTooManyRequests();
});

it('ignores a forwarded IP sent by a client that does not come from Cloudflare (abuse)', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->getJson('/_test/client-ip', ['X-Forwarded-For' => '203.0.113.7'])
        ->assertOk()
        ->assertJson(['ip' => '198.51.100.20']);
});

it('keeps counting a direct client by its connection IP when it rotates fake forwarded IPs (abuse)', function () {
    for ($i = 1; $i <= 10; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->getJson(publicCertificationsUrl(), ['X-Forwarded-For' => "203.0.113.{$i}", 'CF-Connecting-IP' => "203.0.113.{$i}"])
            ->assertOk();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
        ->getJson(publicCertificationsUrl(), ['X-Forwarded-For' => '203.0.113.99', 'CF-Connecting-IP' => '203.0.113.99'])
        ->assertTooManyRequests();
});

it('keeps the IP nginx resolved when that IP is itself inside a Cloudflare range (R7)', function () {
    // The production topology: nginx has already put the visitor's IP in REMOTE_ADDR and
    // overwrites X-Forwarded-For with it, so a visitor inside Cloudflare's ranges (a Worker
    // subrequest) cannot choose its own IP with the header.
    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->getJson('/_test/client-ip', ['X-Forwarded-For' => '173.245.48.10'])
        ->assertOk()
        ->assertJson(['ip' => '173.245.48.10']);
});

it('would take a forwarded IP from a client inside a Cloudflare range, which is why nginx overwrites it (R39)', function () {
    // What Laravel does on its own when the connection IP is in a trusted range: it believes
    // X-Forwarded-For. nginx has already resolved the visitor's IP into REMOTE_ADDR, so if
    // that visitor is itself inside Cloudflare's ranges (a Worker subrequest) the header it
    // sent would be taken. docker/nginx/prod.conf therefore replaces the header with the IP
    // it resolved (NginxProdConfTest); this case documents the risk that line removes.
    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->getJson('/_test/client-ip', ['X-Forwarded-For' => '203.0.113.99'])
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.99']);
});
