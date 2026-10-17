<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 017, RNF: today's appointments (007) are computed in the clinic's timezone. At 23:30 in
 * Mexico City it is already the next day in UTC, and the agenda must still show the clinic's day.
 */

it('lists the appointments of the clinic day late in the evening', function () {
    $this->travelTo(Carbon::parse('2026-10-15 23:30:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
    $today = $this->createAppointment(['date' => '2026-10-15', 'time' => '23:45']);
    $tomorrow = $this->createAppointment(['date' => '2026-10-16', 'time' => '09:00']);

    $response = $this->getJson($this->agendaTodayAppointmentsUrl());

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('id')->all())
        ->toContain($today->id)
        ->not->toContain($tomorrow->id);
});
