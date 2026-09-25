<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 014, "Permisos esperados": every patients route with the actors allowed to use it.
 *
 * @return array<string, list<string>>
 */
function patientsAccessRoutes(): array
{
    $allStaff = ['admin', 'asistente', 'doctor'];
    $clinicalStaff = ['admin', 'asistente'];

    return [
        'GET /patients' => $allStaff,
        'GET /patients/{id}' => $allStaff,
        'PUT /patients/{id}' => ['admin'],
        'POST /patients' => ['admin'],
        'DELETE /patients/{id}' => ['admin'],
        'GET /patients/{patientId}/record' => $allStaff,
        'POST /patients/{patientId}/address' => $clinicalStaff,
        'PUT /patients/{patientId}/address' => $clinicalStaff,
        'DELETE /patients/{patientId}/address' => $clinicalStaff,
        'POST /patients/{patientId}/contact-info' => $clinicalStaff,
        'PUT /patients/{patientId}/contact-info' => $clinicalStaff,
        'DELETE /patients/{patientId}/contact-info' => $clinicalStaff,
        'POST /patients/{patientId}/medical-data' => $clinicalStaff,
        'PUT /patients/{patientId}/medical-data' => $clinicalStaff,
        'DELETE /patients/{patientId}/medical-data' => $clinicalStaff,
    ];
}

/**
 * @return array<string, array{0: string, 1: list<string>}>
 */
function patientsAccessRouteDataset(): array
{
    return collect(patientsAccessRoutes())
        ->mapWithKeys(fn (array $allowed, string $route): array => [$route => [$route, $allowed]])
        ->all();
}

/**
 * @return array<string, string>
 */
function patientsAccessActors(): array
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
 * Every row a rejected request could have touched, so a refusal can prove nothing changed.
 *
 * @return array<string, mixed>
 */
function patientsDataSnapshot(): array
{
    return collect(['patients', 'addresses', 'contact_info', 'medical_data'])
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->orderBy('created_at')->get()->toArray()])
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
        $patientId = $this->createPatient()->id;

        // PUT and DELETE on a sub-resource need it to exist already.
        if (preg_match('#^(PUT|DELETE) /patients/\{patientId\}/(address|contact-info|medical-data)$#', $route, $match)) {
            match ($match[2]) {
                'address' => $this->createAddress(['patient_id' => $patientId]),
                'contact-info' => $this->createContactInfo(['patient_id' => $patientId]),
                'medical-data' => $this->createMedicalData(['patient_id' => $patientId]),
            };
        }

        return match ($route) {
            'GET /patients' => ['getJson', $this->patientsUrl(), []],
            'GET /patients/{id}' => ['getJson', $this->patientUrl($patientId), []],
            'PUT /patients/{id}' => ['putJson', $this->patientUrl($patientId), ['first_name' => 'Jane']],
            'POST /patients' => ['postJson', $this->patientsUrl(), $this->validCreatePatientPayload()],
            'DELETE /patients/{id}' => ['deleteJson', $this->patientUrl($patientId), []],
            'GET /patients/{patientId}/record' => ['getJson', $this->patientRecordUrl($patientId), []],
            'POST /patients/{patientId}/address' => ['postJson', $this->addressUrl($patientId), $this->validCreateAddressPayload()],
            'PUT /patients/{patientId}/address' => ['putJson', $this->addressUrl($patientId), ['city' => 'Shelbyville']],
            'DELETE /patients/{patientId}/address' => ['deleteJson', $this->addressUrl($patientId), []],
            'POST /patients/{patientId}/contact-info' => ['postJson', $this->contactInfoUrl($patientId), $this->validCreateContactInfoPayload()],
            'PUT /patients/{patientId}/contact-info' => ['putJson', $this->contactInfoUrl($patientId), ['emergency_contact' => 'John Roe']],
            'DELETE /patients/{patientId}/contact-info' => ['deleteJson', $this->contactInfoUrl($patientId), []],
            'POST /patients/{patientId}/medical-data' => ['postJson', $this->medicalDataUrl($patientId), $this->validCreateMedicalDataPayload()],
            'PUT /patients/{patientId}/medical-data' => ['putJson', $this->medicalDataUrl($patientId), ['blood_type' => 'A+']],
            'DELETE /patients/{patientId}/medical-data' => ['deleteJson', $this->medicalDataUrl($patientId), []],
        };
    };
});

it('answers each patients route according to the permissions table', function (string $actor, string $route, array $allowed) {
    [$method, $url, $payload] = ($this->buildRequest)($route);
    ($this->actAs)($actor);
    $before = patientsDataSnapshot();

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

    expect(patientsDataSnapshot())->toEqual($before);
})->with(patientsAccessActors())->with(patientsAccessRouteDataset());

it('lets only an administrator change a patient password', function (string $actor, bool $allowed) {
    $patient = $this->createPatient(['password' => Hash::make('0ld-Secret!')]);
    ($this->actAs)($actor);

    $response = $this->putJson($this->patientUrl($patient->id), ['new_password' => 'N3w-Secret!']);

    $storedHash = PatientModel::query()->findOrFail($patient->id)->password;

    if ($allowed) {
        $response->assertOk();
        expect(Hash::check('N3w-Secret!', $storedHash))->toBeTrue();
    } else {
        $response->assertForbidden();
        expect(Hash::check('0ld-Secret!', $storedHash))->toBeTrue();
    }
})->with([
    'admin' => ['admin', true],
    'asistente' => ['asistente', false],
    'doctor' => ['doctor', false],
]);

it('covers every patients route and protects each one with the staff middleware', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/patients'))
        ->map(fn ($route): array => [
            'key' => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first()
                .' '.substr($route->uri(), strlen('api/v1')),
            'middleware' => $route->gatherMiddleware(),
        ]);

    expect($routes->pluck('key')->sort()->values()->all())
        ->toEqual(collect(array_keys(patientsAccessRoutes()))->sort()->values()->all());

    $routes
        ->reject(fn (array $route): bool => in_array($route['key'], ['POST /patients', 'DELETE /patients/{id}'], true))
        ->each(fn (array $route) => expect($route['middleware'])->toContain('staff'));
});
