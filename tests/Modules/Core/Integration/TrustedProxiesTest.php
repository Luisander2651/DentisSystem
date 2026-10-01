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
