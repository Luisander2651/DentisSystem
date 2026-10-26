<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 020 (CA13, CA14, CA16, TM3, TM4, A13): every new route of the dashboard by actor. A
 * refusal for a permission or for ownership comes out of the render of AuthorizationException
 * with its usual body, never from a controller; inactive staff and patients are stopped by the
 * staff middleware and guests by Sanctum. Nothing changes when a request is refused.
 */

const DASHBOARD_ACCESS_NOW = '2026-10-22 11:40:00';

/**
 * @return array<string, list<string>>
 */
function dashboardAccessRoutes(): array
{
    return [
        'GET /agenda/today-summary' => ['admin', 'asistente', 'doctor'],
        'GET /agenda/unclosed' => ['admin', 'asistente', 'doctor'],
        'GET /agenda/without-tracking' => ['admin', 'doctor'],
        'POST /appointments/{id}/tracking' => ['admin', 'doctor'],
    ];
}

/**
 * @param  list<string>  $routes
 * @return array<string, array{0: string, 1: list<string>}>
 */
function dashboardAccessRouteDataset(array $routes): array
{
    return collect(dashboardAccessRoutes())
        ->only($routes)
        ->mapWithKeys(fn (array $allowed, string $route): array => [$route => [$route, $allowed]])
        ->all();
}

/**
 * @return array<string, string>
 */
function dashboardAccessActors(): array
{
    return [
        'admin' => 'admin',
        'asistente' => 'asistente',
        'doctor' => 'doctor',
        'inactive staff' => 'inactive',
        'patient' => 'patient',
        'guest' => 'guest',
    ];
}

/**
 * @return array<string, mixed>
 */
function dashboardAccessSnapshot(): array
{
    return collect(['appointments', 'appointment_tracking', 'appointment_tracking_prescriptions', 'patients'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->toArray()])
        ->all();
}

/**
 * The body a refused actor receives: the render of AuthorizationException for a missing
 * permission, the staff middleware for inactive staff and patients.
 *
 * @return array{error: string}
 */
function dashboardAccessRefusal(string $actor, string $permission): array
{
    return match ($actor) {
        'inactive' => ['error' => 'Your account is inactive.'],
        'patient' => ['error' => 'Only staff can access this resource.'],
        default => ['error' => "You are not allowed to perform this action ({$permission})."],
    };
}

/**
 * Spec 020 (TM3, TM4): an allowed actor gets a success; a refused one, its code and body, and
 * nothing changes. The doctor asks about an appointment of their own.
 *
 * @param  list<string>  $allowed
 */
function dashboardAccessCheck(object $test, string $actor, string $route, array $allowed): void
{
    (function () use ($actor, $route, $allowed): void {
        [$method, $url, $payload] = ($this->buildRequest)($route);
        ($this->actAs)($actor);

        if ($actor === 'doctor') {
            $this->builtAppointment->update(['user_id' => auth()->id()]);
        }

        $before = dashboardAccessSnapshot();

        $response = $this->{$method}($url, $payload);

        if (in_array($actor, $allowed, true)) {
            $response->assertSuccessful();

            return;
        }

        if ($actor === 'guest') {
            $response->assertUnauthorized();
        } else {
            $response->assertForbidden()->assertExactJson(dashboardAccessRefusal($actor, 'appointment-tracking.create'));
        }

        expect(dashboardAccessSnapshot())->toEqual($before);
    })->call($test);
}

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->travelTo(Carbon::parse(DASHBOARD_ACCESS_NOW, 'America/Mexico_City'));

    $this->actAs = function (string $actor): void {
        match ($actor) {
            'admin' => $this->actingAsAdmin(),
            'asistente' => $this->actingAsNonAdminUser('Asistente'),
            'doctor' => $this->actingAsNonAdminUser('Doctor'),
            'inactive' => $this->actingAsInactiveAdmin(),
            'patient' => $this->actingAsPatient(),
            'guest' => null,
        };
    };

    /**
     * A completed appointment of yesterday without a follow-up, so that registering it is
     * allowed for the administrator and for its doctor.
     *
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    $this->buildRequest = function (string $route): array {
        $appointment = $this->createAppointment(['date' => '2026-10-21', 'time' => '10:00', 'status' => 'completada']);
        $this->builtAppointment = $appointment;

        return match ($route) {
            'GET /agenda/today-summary' => ['getJson', '/api/v1/agenda/today-summary', []],
            'GET /agenda/unclosed' => ['getJson', '/api/v1/agenda/unclosed', []],
            'GET /agenda/without-tracking' => ['getJson', '/api/v1/agenda/without-tracking', []],
            'POST /appointments/{id}/tracking' => ['postJson', $this->appointmentUrl($appointment->id).'/tracking', [
                'reason' => 'Dolor en una muela',
                'symptoms' => ['dolor'],
                'diagnosis' => 'Caries',
                'procedure_performed' => 'Resina',
                'prescriptions' => [],
            ]],
        };
    };
});

it('answers today and the unclosed ones according to who asks (abuse)', function (string $actor, string $route, array $allowed) {
    dashboardAccessCheck($this, $actor, $route, $allowed);
})->with(dashboardAccessActors())->with(dashboardAccessRouteDataset(['GET /agenda/today-summary', 'GET /agenda/unclosed']));

it('answers the ones without follow-up according to who asks (abuse)', function (string $actor, string $route, array $allowed) {
    dashboardAccessCheck($this, $actor, $route, $allowed);
})->with(dashboardAccessActors())->with(dashboardAccessRouteDataset(['GET /agenda/without-tracking']))->skip('020: pendiente de T038');

it('answers registering a follow-up according to who asks (abuse)', function (string $actor, string $route, array $allowed) {
    dashboardAccessCheck($this, $actor, $route, $allowed);
})->with(dashboardAccessActors())->with(dashboardAccessRouteDataset(['POST /appointments/{id}/tracking']))->skip('020: pendiente de T041');

it('refuses the doctor the follow-up of an appointment of another doctor, from the render (abuse)', function () {
    [, $url, $payload] = ($this->buildRequest)('POST /appointments/{id}/tracking');
    $this->builtAppointment->update(['user_id' => $this->createUserWithRole('doctor')->id]);
    $this->actingAsNonAdminUser('Doctor');
    $before = dashboardAccessSnapshot();

    $this->postJson($url, $payload)
        ->assertForbidden()
        ->assertExactJson(['error' => 'You are not allowed to perform this action (appointment-tracking.create-own).']);

    expect(dashboardAccessSnapshot())->toEqual($before);
})->skip('020: pendiente de T041');

it('keeps refusing the assistant the month figures and new patients (CA14)', function () {
    $this->actingAsNonAdminUser('Asistente');
    $before = dashboardAccessSnapshot();

    $this->getJson('/api/v1/reports/monthly?month=2026-10')
        ->assertForbidden()
        ->assertExactJson(['error' => 'Only administrators can access this resource.']);

    $this->postJson('/api/v1/patients', [
        'first_name' => 'Nueva',
        'last_name' => 'Paciente',
        'email' => 'nueva.paciente@example.com',
    ])->assertForbidden()
        ->assertExactJson(['error' => 'Only administrators can access this resource.']);

    expect(dashboardAccessSnapshot())->toEqual($before);
});
