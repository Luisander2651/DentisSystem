<?php

declare(strict_types=1);

namespace Tests\Modules\Reports\Integration;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\ActingAsPatient;
use Tests\Support\ActingAsStaff;
use Tests\Support\FakesTwilio;
use Tests\TestCase;

abstract class ReportsIntegrationTestCase extends TestCase
{
    use ActingAsPatient;
    use ActingAsStaff;
    use FakesTwilio;
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
            'date' => '2026-07-06',
            'time' => '10:00',
            'whatsapp_reminder' => false,
            'status' => 'asignada',
        ], $overrides);

        $data['treatment_id'] ??= $this->createTreatment()->id;
        $data['user_id'] ??= $this->createUserWithRole('doctor')->id;
        $data['patient_id'] ??= $this->createPatient()->id;

        return AppointmentModel::create($data);
    }

    protected function monthlyReportUrl(string $month): string
    {
        return '/api/v1/reports/monthly?month='.$month;
    }

    protected function monthlyReportExcelUrl(string $month): string
    {
        return '/api/v1/reports/monthly/excel?month='.$month;
    }

    protected function reportsScreenUrl(?string $month = null): string
    {
        return $month === null ? '/reportes' : '/reportes?mes='.$month;
    }
}
