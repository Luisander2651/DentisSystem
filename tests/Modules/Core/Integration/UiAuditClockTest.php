<?php

declare(strict_types=1);

use App\Core\Middlewares\UiAuditClock;
use Carbon\CarbonImmutable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Core\Integration\CoreIntegrationTestCase;

uses(CoreIntegrationTestCase::class);

/*
 * Spec 020 (D16, TM9, A27, A38): `npm run test:ui` opens the dashboard on 17 September 2026 at
 * 11:40 by sending X-Ui-Audit-Now. Only in local and testing does the header fix now() for that
 * request; the middleware sits in the web and api groups whatever the environment, and checks
 * the environment on every request. The cookies of the response still expire by the real clock.
 */

const UI_AUDIT_CLOCK_NOW = '2026-09-17T11:40:00-06:00';

beforeEach(function () {
    Route::middleware('web')->get('/__ui-audit-clock', fn () => response()->json(['now' => now()->toIso8601String()]));
    Route::middleware('api')->get('/api/__ui-audit-clock', fn () => response()->json(['now' => now()->toIso8601String()]));
});

it('fixes now() of the request to the header in testing', function (string $path) {
    $now = $this->getJson($path, ['X-Ui-Audit-Now' => UI_AUDIT_CLOCK_NOW])->assertOk()->json('now');

    expect(CarbonImmutable::parse($now)->equalTo(CarbonImmutable::parse(UI_AUDIT_CLOCK_NOW)))->toBeTrue();
})->with(['web' => '/__ui-audit-clock', 'api' => '/api/__ui-audit-clock'])->skip('020: pendiente de T007');

it('leaves the clock of the next request alone', function () {
    $this->getJson('/api/__ui-audit-clock', ['X-Ui-Audit-Now' => UI_AUDIT_CLOCK_NOW])->assertOk();

    $now = $this->getJson('/api/__ui-audit-clock')->assertOk()->json('now');

    expect(abs(CarbonImmutable::parse($now)->diffInSeconds(CarbonImmutable::createFromTimestamp(time()))))->toBeLessThan(60);
})->skip('020: pendiente de T007');

it('ignores a header that is not a date', function (string $value) {
    $now = $this->getJson('/api/__ui-audit-clock', ['X-Ui-Audit-Now' => $value])->assertOk()->json('now');

    expect(abs(CarbonImmutable::parse($now)->diffInSeconds(CarbonImmutable::createFromTimestamp(time()))))->toBeLessThan(60);
})->with(['texto' => 'mañana', 'vacío' => '', 'fecha imposible' => '2026-02-30T11:40:00-06:00'])->skip('020: pendiente de T007');

it('has no effect outside local and testing (abuse)', function (string $environment) {
    $this->app['env'] = $environment;

    $now = $this->getJson('/api/__ui-audit-clock', ['X-Ui-Audit-Now' => UI_AUDIT_CLOCK_NOW])->assertOk()->json('now');

    expect(CarbonImmutable::parse($now)->isSameDay(CarbonImmutable::parse(UI_AUDIT_CLOCK_NOW)))->toBeFalse()
        ->and(abs(CarbonImmutable::parse($now)->diffInSeconds(CarbonImmutable::createFromTimestamp(time()))))->toBeLessThan(60);
})->with(['production', 'staging'])->skip('020: pendiente de T007');

it('sits in the web and api groups whatever the environment (A27)', function () {
    $groups = app(Router::class)->getMiddlewareGroups();

    expect($groups['web'])->toContain(UiAuditClock::class)
        ->and($groups['api'])->toContain(UiAuditClock::class);
})->skip('020: pendiente de T007');

it('serves the dashboard with the header', function () {
    $this->actingAsAdmin();

    $this->get('/dashboard', ['X-Ui-Audit-Now' => UI_AUDIT_CLOCK_NOW])->assertOk();
})->skip('020: pendiente de T007');

it('lets the cookies of the response expire by the real clock (A38)', function () {
    $response = $this->get('/__ui-audit-clock', ['X-Ui-Audit-Now' => UI_AUDIT_CLOCK_NOW])->assertOk();

    $cookies = collect($response->headers->getCookies())->keyBy(fn ($cookie) => $cookie->getName());

    expect($cookies->keys()->all())->toContain('XSRF-TOKEN', config('session.cookie'))
        ->and($cookies['XSRF-TOKEN']->getExpiresTime())->toBeGreaterThan(time())
        ->and($cookies[config('session.cookie')]->getExpiresTime())->toBeGreaterThan(time());
})->skip('020: pendiente de T007');
