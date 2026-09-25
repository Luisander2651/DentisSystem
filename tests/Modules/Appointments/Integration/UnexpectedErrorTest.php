<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Repositories\AppointmentsRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 014, CA12: an unexpected error in the agenda and in the treatments catalog answers a
 * generic 500, without the technical message of the failure.
 */
it('answers a generic 500 when listing appointments fails unexpectedly', function () {
    $this->actingAsAdmin();
    $this->mock(AppointmentsRepositoryInterface::class)
        ->shouldReceive('findAllByStatusAndDateOrPatientId')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson($this->appointmentsUrl())
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});

it('answers a generic 500 when updating a treatment fails unexpectedly', function () {
    $this->actingAsAdmin();
    $treatment = $this->createTreatment();
    // TreatmentsService depends on the final EloquentTreatmentRepository (known P3 leak), so it
    // cannot be mocked. Renaming the table makes the real query fail; PostgreSQL DDL is
    // transactional, so RefreshDatabase rolls it back.
    DB::statement('ALTER TABLE treatments RENAME TO treatments_unavailable');

    $this->putJson($this->treatmentAdminUrl($treatment->id), ['name' => 'Limpieza profunda'])
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);
});
