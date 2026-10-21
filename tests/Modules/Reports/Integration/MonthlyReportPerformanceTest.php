<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Modules\Reports\Integration\ReportsIntegrationTestCase;

uses(ReportsIntegrationTestCase::class);

/*
 * Spec 019, non-functional requirement: the report of a month with up to 2 000 appointments is
 * shown and downloaded in less than 3 s each.
 */

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 10:00:00', 'America/Mexico_City'));
    $this->actingAsAdmin();
});

it('gives the report and the file of a month with 2 000 appointments in less than 3 s each', function () {
    $treatments = array_map(fn (int $index): int => $this->createTreatment(['name' => "Tratamiento {$index}"])->id, range(1, 8));
    $doctors = array_map(fn (int $index): string => $this->createUserWithRole('doctor', ['first_name' => "Doctor{$index}", 'last_name' => 'Prueba'])->id, range(1, 6));
    $patients = array_map(fn (): string => $this->createPatient()->id, range(1, 60));
    $statuses = ['completada', 'completada', 'asignada', 'reprogramada', 'cancelada'];
    $rows = [];

    foreach (range(0, 1999) as $index) {
        $rows[] = [
            'id' => (string) Str::uuid(),
            'date' => sprintf('2026-07-%02d', $index % 31 + 1),
            'time' => sprintf('%02d:%02d', 8 + $index % 11, $index % 2 * 30),
            'whatsapp_reminder' => false,
            'status' => $statuses[$index % count($statuses)],
            'treatment_id' => $treatments[$index % count($treatments)],
            'user_id' => $doctors[$index % count($doctors)],
            'patient_id' => $patients[$index % count($patients)],
            'created_at' => '2026-06-01 10:00:00',
            'updated_at' => '2026-06-01 10:00:00',
        ];
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        AppointmentModel::query()->insert($chunk);
    }

    expect(AppointmentModel::query()->count())->toBe(2000);

    $started = microtime(true);
    $this->getJson($this->monthlyReportUrl('2026-07'))->assertOk()->assertJsonPath('data.appointments.total.value', 2000);
    $report = microtime(true) - $started;

    $started = microtime(true);
    $this->get($this->monthlyReportExcelUrl('2026-07'))->assertOk();
    $file = microtime(true) - $started;

    expect($report)->toBeLessThan(3.0, "the report took {$report} s")
        ->and($file)->toBeLessThan(3.0, "the file took {$file} s");
})->skip('019: pendiente de T038 y T041');
