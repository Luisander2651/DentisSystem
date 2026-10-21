<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, CA10, TM1 and TM2: only an active administrator gets the report or its file. Every
 * other actor is refused by the server, with a valid or an invalid month, and gets neither the
 * figures nor a file. The "Sin permiso" page of /reportes is checked by WebAccessDeniedPagesTest
 * and the redirection of a visitor by GuestRedirectTest.
 */

const REPORTS_ENDPOINTS = [
    'report, valid month' => '/api/v1/reports/monthly?month=2026-07',
    'report, invalid month' => '/api/v1/reports/monthly?month=2026-13',
    'file, valid month' => '/api/v1/reports/monthly/excel?month=2026-07',
    'file, invalid month' => '/api/v1/reports/monthly/excel?month=2026-13',
];

beforeEach(function () {
    $this->fakeTwilio();
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->createAppointment(['date' => '2026-07-06', 'status' => 'completada']);
});

dataset('reports endpoints', REPORTS_ENDPOINTS);

dataset('refused actors', [
    'assistant' => fn () => fn () => $this->actingAsNonAdminUser('Asistente'),
    'doctor' => fn () => fn () => $this->actingAsNonAdminUser('Doctor'),
    'inactive administrator' => fn () => fn () => $this->actingAsInactiveAdmin(),
    'patient' => fn () => fn () => $this->actingAsPatient(),
]);

it('refuses the report and its file to every actor but an active administrator (abuse)', function (Closure $actAs, string $url) {
    $actAs->call($this);

    $response = $this->getJson($url);

    $response->assertForbidden();
    expect($response->headers->get('Content-Disposition'))->toBeNull()
        ->and($response->json('data'))->toBeNull()
        ->and(array_keys($response->json()))->toBe(['error']);
})->with('refused actors')->with('reports endpoints')->skip('019: pendiente de T038 y T041');

it('asks a visitor without a session to sign in, with neither figures nor a file (abuse)', function (string $url) {
    $response = $this->getJson($url);

    $response->assertUnauthorized();
    expect($response->headers->get('Content-Disposition'))->toBeNull()
        ->and($response->json('data'))->toBeNull();
})->with('reports endpoints')->skip('019: pendiente de T038 y T041');

it('gives the report and its file to an active administrator', function () {
    $this->actingAsAdmin();

    $this->getJson(REPORTS_ENDPOINTS['report, valid month'])->assertOk()->assertJsonPath('data.appointments.total.value', 1);
    $this->get(REPORTS_ENDPOINTS['file, valid month'])->assertOk()->assertDownload('reporte-dentissa-2026-07.xlsx');
})->skip('019: pendiente de T038 y T041');
