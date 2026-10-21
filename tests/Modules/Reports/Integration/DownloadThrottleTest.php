<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA14 and TM5: each administrator gets at most 10 files a minute; the 11th is refused
 * with the message of the spec, and the report on screen has no limit of its own.
 */

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
});

it('refuses the 11th download of a minute with the message of the spec, without a file (abuse)', function () {
    $this->actingAsAdmin();

    foreach (range(1, 10) as $download) {
        $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
    }

    $refused = $this->getJson($this->monthlyReportExcelUrl('2026-07'));

    $refused->assertTooManyRequests()
        ->assertExactJson(['error' => 'Demasiadas descargas. Espera un momento e inténtalo de nuevo.']);
    expect($refused->headers->get('Content-Disposition'))->toBeNull();
});

it('counts the downloads of each administrator apart', function () {
    $this->actingAsAdmin();

    foreach (range(1, 11) as $download) {
        $this->get($this->monthlyReportExcelUrl('2026-07'));
    }

    $this->app['auth']->forgetGuards();
    $this->actingAsAdmin();

    $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
});

it('lets the administrator download again once the minute has passed', function () {
    $this->actingAsAdmin();

    foreach (range(1, 11) as $download) {
        $this->get($this->monthlyReportExcelUrl('2026-07'));
    }

    $this->travel(61)->seconds();

    $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
});

it('does not limit the report on screen with the limit of the downloads', function () {
    $this->actingAsAdmin();

    foreach (range(1, 11) as $request) {
        $this->getJson($this->monthlyReportUrl('2026-07'))->assertOk();
    }
});
