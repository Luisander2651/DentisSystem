<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 014, "Permisos esperados", as spec 018 changed it: every agenda and appointments route
 * behind the staff middleware, with the actors allowed to use it. Viewing the detail and the
 * history of a patient's appointments is for every active staff member; the assistant manages
 * the agenda except completing and deleting; the doctor lists their own appointments and
 * completes and reads the follow-up of their own ones.
 *
 * @return array<string, list<string>>
 */
function appointmentsAccessRoutes(): array
{
    $allStaff = ['admin', 'asistente', 'doctor'];
    $agendaManagers = ['admin', 'asistente'];

    return [
        'GET /agenda/patients' => $agendaManagers,
        'GET /agenda/doctors' => $agendaManagers,
        'GET /agenda/treatments' => $agendaManagers,
        'GET /agenda/today-appointments' => $allStaff,
        'GET /appointments' => $allStaff,
        'POST /appointments' => $agendaManagers,
        'GET /appointments/patient/{patientId}' => $allStaff,
        'GET /appointments/{id}' => $allStaff,
        'PUT /appointments/{id}' => $agendaManagers,
        'DELETE /appointments/{id}' => ['admin'],
        'POST /appointments/{id}/complete' => ['admin', 'doctor'],
        'GET /appointments/{id}/tracking' => ['admin', 'doctor'],
    ];
}

/**
 * Spec 018 (/analyze A19): what these routes answer an allowed actor depends on who owns the
 * appointment and on whether it has a follow-up, so this table only checks that they refuse
 * the actors who are not allowed. Ownership and success are covered by DoctorTrackingTest.
 *
 * @return list<string>
 */
function appointmentsDenialOnlyRoutes(): array
{
    return ['POST /appointments/{id}/complete', 'GET /appointments/{id}/tracking'];
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
    // Spec 017: the clock is fixed the day before the dates booked and moved below, so the
    // administrator's rows keep answering 2xx and do not turn into a "date in the past" 422.
    $this->travelTo(Carbon::parse('2026-08-31 10:00:00', 'America/Mexico_City'));

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
        $this->builtAppointment = $appointment;

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
            'POST /appointments/{id}/complete' => ['postJson', $this->appointmentUrl($appointment->id).'/complete', [
                'reason' => 'Dolor en una muela',
                'symptoms' => ['dolor'],
                'diagnosis' => 'Caries',
                'procedure_performed' => 'Resina',
            ]],
            'GET /appointments/{id}/tracking' => ['getJson', $this->appointmentUrl($appointment->id).'/tracking', []],
        };
    };
});

it('answers each agenda and appointments route according to the permissions table', function (string $actor, string $route, array $allowed) {
    [$method, $url, $payload] = ($this->buildRequest)($route);
    ($this->actAs)($actor);

    if ($actor === 'doctor' && in_array($route, appointmentsDenialOnlyRoutes(), true)) {
        // The doctor is allowed on their own appointments only; ownership is DoctorTrackingTest's.
        $this->builtAppointment->update(['user_id' => auth()->id()]);
    }

    $before = appointmentsDataSnapshot();

    $response = $this->{$method}($url, $payload);

    if (in_array($actor, $allowed, true)) {
        if (in_array($route, appointmentsDenialOnlyRoutes(), true)) {
            expect($response->status())->not->toBeIn([401, 403]);

            return;
        }

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

it('refuses to change an appointment to the doctor, inactive staff, patients and guests, whatever the body (spec 017 CA10, spec 018 CA7)', function (string $actor, array $payload) {
    $appointment = $this->createAppointment();
    ($this->actAs)($actor);
    $before = appointmentsDataSnapshot();

    $response = $this->putJson($this->appointmentUrl($appointment->id), $payload);

    $actor === 'guest' ? $response->assertUnauthorized() : $response->assertForbidden();
    expect(appointmentsDataSnapshot())->toEqual($before);
})->with([
    'doctor' => 'doctor',
    'inactive staff' => 'inactive',
    'patient' => 'patient',
    'guest' => 'guest',
])->with([
    'reschedule' => [['date' => '2026-09-02', 'time' => '11:00']],
    'cancel' => [['status' => 'cancelada']],
    'complete' => [['status' => 'completada']],
    'date without time' => [['date' => '2026-09-02']],
    'status outside the list' => [['status' => 'asignada']],
    'reschedule and status at once' => [['date' => '2026-09-02', 'time' => '11:00', 'status' => 'cancelada']],
    'date in another format' => [['date' => '02/09/2026', 'time' => '11:00']],
]);

it('keeps the clinical tracking of an appointment away from the assistant and from a doctor who does not own it', function (string $role) {
    // The appointment belongs to an administrator, so the doctor is refused as a stranger.
    $appointment = $this->createAppointment();
    $this->actingAsNonAdminUser($role);

    $this->getJson($this->appointmentUrl($appointment->id).'/tracking')->assertForbidden();
})->with(['Asistente', 'Doctor']);

it('refuses the assistant completing an appointment through the edit or deleting it (spec 018 CA4)', function (string $method, array $payload) {
    $appointment = $this->createAppointment();
    $this->actingAsNonAdminUser('Asistente');
    $before = appointmentsDataSnapshot();

    $this->{$method}($this->appointmentUrl($appointment->id), $payload)->assertForbidden();

    expect(appointmentsDataSnapshot())->toEqual($before);
})->with([
    'complete through the edit' => ['putJson', ['status' => 'completada']],
    'delete' => ['deleteJson', []],
]);

it('answers today appointments to every staff role and the doctors selector to the administrator and the assistant (spec 018)', function (string $url, string $role, bool $allowed) {
    $this->actingAsNonAdminUser($role);

    $response = $this->getJson($url);

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with([
    'today appointments, assistant' => ['/api/v1/agenda/today-appointments', 'Asistente', true],
    'today appointments, doctor' => ['/api/v1/agenda/today-appointments', 'Doctor', true],
    'doctors selector, assistant' => ['/api/v1/agenda/doctors', 'Asistente', true],
    'doctors selector, doctor' => ['/api/v1/agenda/doctors', 'Doctor', false],
    'treatments selector, assistant' => ['/api/v1/agenda/treatments', 'Asistente', true],
    'treatments selector, doctor' => ['/api/v1/agenda/treatments', 'Doctor', false],
]);

it('keeps the management of users and of the catalogue away from the assistant (spec 018, D11)', function (string $url) {
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson($url)->assertForbidden();
})->with([
    'users' => '/api/v1/users',
    'treatments' => '/api/v1/treatments',
]);

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
