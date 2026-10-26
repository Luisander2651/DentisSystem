<?php

declare(strict_types=1);

namespace Tests\Modules\AppointmentTracking\Integration;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use App\Modules\AppointmentTracking\Domain\Entities\AppointmentTrackingEntity;
use App\Modules\AppointmentTracking\Domain\Repositories\AppointmentTrackingRepositoryInterface;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingAppointmentId;
use App\Modules\AppointmentTracking\Domain\ValueObjects\AppointmentTrackingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

abstract class AppointmentTrackingIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use RefreshDatabase;

    protected function createTreatment(array $overrides = []): TreatmentModel
    {
        return TreatmentModel::create(array_merge([
            'name' => 'Limpieza dental',
            'description' => 'Limpieza dental profunda',
            'time' => 30,
        ], $overrides));
    }

    protected function createAppointment(array $overrides = []): AppointmentModel
    {
        $data = array_merge([
            'id' => (string) Str::uuid(),
            'date' => '2026-09-01',
            'time' => '10:00',
            'whatsapp_reminder' => false,
            'status' => 'asignada',
        ], $overrides);

        $data['treatment_id'] ??= $this->createTreatment()->id;
        $data['user_id'] ??= $this->createUserWithRole('administrador')->id;
        $data['patient_id'] ??= $this->createPatient()->id;

        return AppointmentModel::create($data);
    }

    /**
     * Spec 020 (TM5): the second of two simultaneous submissions. The follow-up is already in the
     * database, but the use case does not see it yet, so only the unique constraint stops it.
     */
    protected function missExistingTrackingOnRead(): void
    {
        $repository = $this->app->make(AppointmentTrackingRepositoryInterface::class);

        $this->app->instance(AppointmentTrackingRepositoryInterface::class, new class($repository) implements AppointmentTrackingRepositoryInterface
        {
            public function __construct(private readonly AppointmentTrackingRepositoryInterface $repository) {}

            public function save(AppointmentTrackingEntity $appointmentTracking): AppointmentTrackingEntity
            {
                return $this->repository->save($appointmentTracking);
            }

            public function findById(AppointmentTrackingId $id): ?AppointmentTrackingEntity
            {
                return $this->repository->findById($id);
            }

            public function findByAppointmentId(AppointmentTrackingAppointmentId $appointmentId): ?AppointmentTrackingEntity
            {
                return null;
            }

            public function delete(AppointmentTrackingId $id): void
            {
                $this->repository->delete($id);
            }
        });
    }

    protected function completeAppointmentUrl(string $appointmentId): string
    {
        return "/api/v1/appointments/{$appointmentId}/complete";
    }

    protected function validCompletePayload(array $overrides = []): array
    {
        return array_merge([
            'reason' => 'Dolor persistente',
            'symptoms' => ['dolor', 'sensibilidad'],
            'diagnosis' => 'Caries profunda',
            'procedure_performed' => 'Endodoncia',
            'observations' => null,
            'recommendations' => null,
            'prescriptions' => [],
        ], $overrides);
    }

    protected function validPrescriptionPayload(array $overrides = []): array
    {
        return array_merge([
            'medication' => 'Ibuprofeno',
            'dosage' => '400mg',
            'durationDays' => 7,
            'dailyFrequency' => 3,
            'instructions' => 'Tomar con alimentos',
        ], $overrides);
    }
}
