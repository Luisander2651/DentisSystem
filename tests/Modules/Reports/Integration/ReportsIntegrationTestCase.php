<?php

declare(strict_types=1);

namespace Tests\Modules\Reports\Integration;

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\TreatmentModel;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use App\Modules\Users\Infrastructure\Persistence\Eloquent\Models\UserModel;
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

    protected function patientCreatedOn(string $createdAt, string $status = 'active'): PatientModel
    {
        $patient = $this->createPatient(['status' => $status]);
        PatientModel::query()->whereKey($patient->id)->update(['created_at' => $createdAt]);

        return $patient;
    }

    /**
     * July 2026 of design.md: 8 appointments (2 completed, 1 cancelled, 5 unclosed) of two
     * doctors and three treatments, for two patients registered in May; Monday 2, Tuesday 1,
     * Wednesday 4; 9 h 2, 10 h 1, 11 h 1, 12 h 2, 16 h 1.
     *
     * @return array{daniela: UserModel, diego: UserModel}
     */
    protected function seedJuly2026(): array
    {
        $cleaning = $this->createTreatment(['name' => 'Limpieza dental de prueba']);
        $orthodontics = $this->createTreatment(['name' => 'Ortodoncia de prueba']);
        $resin = $this->createTreatment(['name' => 'Resina de prueba']);
        $daniela = $this->createUserWithRole('doctor', ['first_name' => 'Daniela', 'last_name' => 'Prueba']);
        $diego = $this->createUserWithRole('doctor', ['first_name' => 'Diego', 'last_name' => 'Prueba']);
        $ana = $this->patientCreatedOn('2026-05-04 10:00:00');
        $luis = $this->patientCreatedOn('2026-05-11 10:00:00');

        foreach ([
            ['2026-07-06', '09:00', $cleaning, $daniela, $ana, 'completada'],
            ['2026-07-13', '12:00', $cleaning, $daniela, $luis, 'completada'],
            ['2026-07-07', '10:00', $orthodontics, $daniela, $ana, 'asignada'],
            ['2026-07-08', '09:00', $orthodontics, $daniela, $luis, 'reprogramada'],
            ['2026-07-08', '11:00', $resin, $daniela, $ana, 'asignada'],
            ['2026-07-15', '12:00', $resin, $diego, $luis, 'asignada'],
            ['2026-07-15', '16:00', $cleaning, $diego, $ana, 'asignada'],
            ['2026-07-09', '10:00', $resin, $diego, $luis, 'cancelada'],
        ] as [$date, $time, $treatment, $doctor, $patient, $status]) {
            $this->createAppointment(['date' => $date, 'time' => $time, 'treatment_id' => $treatment->id, 'user_id' => $doctor->id, 'patient_id' => $patient->id, 'status' => $status]);
        }

        return ['daniela' => $daniela, 'diego' => $diego];
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
