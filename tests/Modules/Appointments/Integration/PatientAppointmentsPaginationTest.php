<?php

declare(strict_types=1);

use App\Core\Authorization\AuthorizationServiceInterface;
use App\Core\Authorization\Exceptions\AuthorizationException;
use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Appointments\Aplication\DTOs\GetAppointmentsByIdPatientDto;
use App\Modules\Appointments\Aplication\UseCases\ListPatientAppointmentsPageUseCase;
use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 028 (CA2, CA6, CA9, CA13, CA14; P5, P6): the history of appointments of the clinical record
 * answers ten per page with its `meta`, the most recent first.
 */
beforeEach(function () {
    $this->patient = $this->createPatient();
    $this->seedHistory = function (int $count): void {
        $treatmentId = $this->createTreatment()->id;
        $doctorId = $this->createUserWithRole('Doctor')->id;

        foreach (range(1, $count) as $number) {
            $this->createAppointment([
                'patient_id' => $this->patient->id,
                'treatment_id' => $treatmentId,
                'user_id' => $doctorId,
                'date' => sprintf('2026-01-%02d', $number),
                'status' => 'completada',
            ]);
        }
    };
});

it('answers ten appointments per page with their meta, the most recent first', function () {
    $this->actingAsNonAdminUser('Asistente');
    ($this->seedHistory)(25);

    $this->getJson("/api/v1/appointments/patient/{$this->patient->id}")
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('data.0.date', '2026-01-25')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 10,
            'total' => 25,
            'last_page' => 3,
            'from' => 1,
            'to' => 10,
        ]);
});

it('answers the page asked for, the last one beyond it and never more than fifty', function (string $query, int $count, int $page) {
    $this->actingAsNonAdminUser('Asistente');
    ($this->seedHistory)(25);

    $this->getJson("/api/v1/appointments/patient/{$this->patient->id}".$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'third page' => ['?page=3', 5, 3],
    'beyond the last' => ['?page=99', 5, 3],
    'ten thousand per page' => ['?per_page=10000', 25, 1],
]);

it('answers an empty page for a patient without appointments or that does not exist', function (bool $exists) {
    $this->actingAsNonAdminUser('Asistente');
    $patientId = $exists ? $this->patient->id : (string) Str::uuid();

    $this->getJson("/api/v1/appointments/patient/{$patientId}")
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.from', null);
})->with(['without appointments' => true, 'unknown' => false]);

it('refuses a patient with 403 and without meta', function () {
    ($this->seedHistory)(3);
    $this->actingAsPatient();

    $this->getJson("/api/v1/appointments/patient/{$this->patient->id}")
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
});

it('rejects invalid page parameters with 422', function (string $query, string $field) {
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson("/api/v1/appointments/patient/{$this->patient->id}".$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
]);

it('neither reads nor counts the history when the permission is denied', function () {
    $this->mock(AppointmentsRepositoryInterface::class)->shouldNotReceive('findPageByPatient');
    $this->app->instance(AuthorizationServiceInterface::class, new class implements AuthorizationServiceInterface
    {
        public function assertCan(string $permission): void
        {
            throw AuthorizationException::forbidden($permission);
        }

        public function can(string $permission): bool
        {
            return false;
        }

        public function currentActorId(): string
        {
            throw AuthorizationException::unauthenticated();
        }

        public function currentPatientId(): string
        {
            throw AuthorizationException::unauthenticated();
        }
    });

    expect(fn () => app(ListPatientAppointmentsPageUseCase::class)->execute(
        GetAppointmentsByIdPatientDto::create(patientId: $this->patient->id),
        PageRequest::create(1, 10),
    ))->toThrow(AuthorizationException::class);
});
