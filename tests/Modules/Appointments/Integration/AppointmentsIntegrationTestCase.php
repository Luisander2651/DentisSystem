<?php

declare(strict_types=1);

namespace Tests\Modules\Appointments\Integration;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

abstract class AppointmentsIntegrationTestCase extends TestCase
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

    protected function appointmentsUrl(): string
    {
        return '/api/v1/appointments';
    }

    protected function appointmentUrl(string $id): string
    {
        return "/api/v1/appointments/{$id}";
    }

    protected function agendaTreatmentsUrl(): string
    {
        return '/api/v1/agenda/treatments';
    }

    protected function agendaTodayAppointmentsUrl(): string
    {
        return '/api/v1/agenda/today-appointments';
    }

    protected function treatmentsAdminUrl(): string
    {
        return '/api/v1/treatments';
    }

    protected function treatmentAdminUrl(int|string $id): string
    {
        return "/api/v1/treatments/{$id}";
    }

    protected function validCreateAppointmentPayload(array $overrides = []): array
    {
        $treatmentId = $overrides['treatment_id'] ?? $this->createTreatment()->id;
        $userId = $overrides['user_id'] ?? $this->createUserWithRole('administrador')->id;
        $patientId = $overrides['patient_id'] ?? $this->createPatient()->id;

        return array_merge([
            'date' => '2026-09-01',
            'time' => '10:00',
            'treatment_id' => (string) $treatmentId,
            'user_id' => $userId,
            'patient_id' => $patientId,
        ], $overrides);
    }

    protected function validCreateTreatmentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Blanqueamiento dental',
            'description' => 'Blanqueamiento dental con luz LED',
            'time' => '45',
        ], $overrides);
    }
}
