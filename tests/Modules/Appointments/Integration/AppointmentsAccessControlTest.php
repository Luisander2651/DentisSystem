<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 014, "Permisos esperados": every agenda and appointments route behind the staff
 * middleware, with the actors allowed to use it. Viewing the detail and the history of a
 * patient's appointments is for every active staff member; the rest of the agenda is only
 * for the administrator.
 *
 * @return array<string, list<string>>
 */
function appointmentsAccessRoutes(): array
{
    $allStaff = ['admin', 'asistente', 'doctor'];

    return [
        'GET /agenda/patients' => ['admin'],
        'GET /agenda/doctors' => ['admin'],
        'GET /agenda/treatments' => ['admin'],
        'GET /agenda/today-appointments' => ['admin'],
        'GET /appointments' => ['admin'],
        'POST /appointments' => ['admin'],
        'GET /appointments/patient/{patientId}' => $allStaff,
        'GET /appointments/{id}' => $allStaff,
        'PUT /appointments/{id}' => ['admin'],
        'DELETE /appointments/{id}' => ['admin'],
    ];
}

/**
 * @return array<string, array{0: string, 1: list<string>}>
 */
function appointmentsAccessRouteDataset(): array
{
    return collect(appointmentsAccessRoutes())
        ->mapWithKeys(fn (array $allowed, string $route): array => [$route => [$route, $allowed]])
        ->all();
}

/**
 * @return array<string, string>
 */
function appointmentsAccessActors(): array
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
function appointmentsDataSnapshot(): array
{
    return collect(['appointments', 'treatments'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('id')->get()->toArray()])
        ->all();
}

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);

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
     * Prepares the fixtures a route needs and returns [method, url, payload].
     *
     * @return array{0: string, 1: string, 2: array<string, mixed>}
     */
    $this->buildRequest = function (string $route): array {
        $appointment = $this->createAppointment();

        return match ($route) {
            'GET /agenda/patients' => ['getJson', '/api/v1/agenda/patients', []],
            'GET /agenda/doctors' => ['getJson', '/api/v1/agenda/doctors', []],
            'GET /agenda/treatments' => ['getJson', $this->agendaTreatmentsUrl(), []],
            'GET /agenda/today-appointments' => ['getJson', $this->agendaTodayAppointmentsUrl(), []],
            'GET /appointments' => ['getJson', $this->appointmentsUrl(), []],
            'POST /appointments' => ['postJson', $this->appointmentsUrl(), $this->validCreateAppointmentPayload(['date' => '2026-09-03'])],
            'GET /appointments/patient/{patientId}' => ['getJson', $this->appointmentsUrl().'/patient/'.$appointment->patient_id, []],
            'GET /appointments/{id}' => ['getJson', $this->appointmentUrl($appointment->id), []],
            'PUT /appointments/{id}' => ['putJson', $this->appointmentUrl($appointment->id), ['date' => '2026-09-02', 'time' => '11:00']],
            'DELETE /appointments/{id}' => ['deleteJson', $this->appointmentUrl($appointment->id), []],
        };
    };
});

it('answers each agenda and appointments route according to the permissions table', function (string $actor, string $route, array $allowed) {
    [$method, $url, $payload] = ($this->buildRequest)($route);
    ($this->actAs)($actor);
    $before = appointmentsDataSnapshot();

    $response = $this->{$method}($url, $payload);

    if (in_array($actor, $allowed, true)) {
        $response->assertSuccessful();

        return;
    }

    if ($actor === 'guest') {
        $response->assertUnauthorized();
    } else {
        $response->assertForbidden();
    }

    expect(appointmentsDataSnapshot())->toEqual($before);
})->with(appointmentsAccessActors())->with(appointmentsAccessRouteDataset());

it('keeps the clinical tracking of an appointment away from assistants and doctors', function (string $role) {
    $appointment = $this->createAppointment();
    $this->actingAsNonAdminUser($role);

    $this->getJson($this->appointmentUrl($appointment->id).'/tracking')->assertForbidden();
})->with(['Asistente', 'Doctor']);

it('refuses today appointments and the doctors selector to assistants and doctors with 403, not 500', function (string $url, string $role) {
    $this->actingAsNonAdminUser($role);

    $this->getJson($url)->assertForbidden();
})->with([
    'today appointments' => '/api/v1/agenda/today-appointments',
    'doctors selector' => '/api/v1/agenda/doctors',
])->with(['Asistente', 'Doctor']);

it('returns the agenda treatments catalog to the administrator ordered by name, with a null duration as 0', function () {
    $this->createTreatment(['name' => 'Ortodoncia', 'time' => 60]);
    $this->createTreatment(['name' => 'Blanqueamiento', 'time' => null]);
    $this->createTreatment(['name' => 'Limpieza', 'time' => 30]);
    $this->actingAsAdmin();

    $response = $this->getJson($this->agendaTreatmentsUrl())->assertOk();

    expect(collect($response->json('data'))->pluck('name')->all())
        ->toBe(['Blanqueamiento', 'Limpieza', 'Ortodoncia'])
        ->and($response->json('data.0.time'))->toBe(0);
});

it('covers every agenda and appointments route and protects each one with the staff middleware', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/agenda')
            || str_starts_with($route->uri(), 'api/v1/appointments'))
        ->reject(fn ($route): bool => in_array('only.admin', $route->gatherMiddleware(), true))
        ->map(fn ($route): array => [
            'key' => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first()
                .' '.substr($route->uri(), strlen('api/v1')),
            'middleware' => $route->gatherMiddleware(),
        ]);

    expect($routes->pluck('key')->sort()->values()->all())
        ->toEqual(collect(array_keys(appointmentsAccessRoutes()))->sort()->values()->all());

    $routes->each(fn (array $route) => expect($route['middleware'])->toContain('staff'));
});
