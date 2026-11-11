<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 025 (RNF, TM11): with 100 appointments the portal still answers quickly. The time depends
 * on the machine (it is measured by hand in T050), so here each read makes a fixed number of
 * queries, the same with 5 appointments as with 100: no N+1, nothing that grows with the data.
 */

/**
 * @param  list<int>  $treatments
 * @param  list<string>  $doctors
 */
function portalPerformanceAppointments(array $treatments, array $doctors, string $patientId, int $count): void
{

    $rows = array_map(fn (int $index): array => [
        'id' => (string) Str::uuid(),
        'date' => (new DateTimeImmutable('2026-01-05'))->modify('+'.($index * 3).' days')->format('Y-m-d'),
        'time' => sprintf('%02d:%02d', 8 + $index % 10, $index % 2 * 30),
        'whatsapp_reminder' => false,
        'status' => ['completada', 'asignada', 'reprogramada', 'cancelada'][$index % 4],
        'treatment_id' => $treatments[$index % count($treatments)],
        'user_id' => $doctors[$index % count($doctors)],
        'patient_id' => $patientId,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], range(0, $count - 1));

    AppointmentModel::query()->insert($rows);
}

function portalQueriesOf(object $test, string $url): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $test->getJson($url)->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $queries;
}

it('makes the same number of queries with 5 appointments as with 100', function (string $url) {
    $this->travelTo(Carbon::parse('2026-09-17 11:40:00', 'America/Mexico_City'));

    $treatments = array_map(fn (int $index): int => $this->createTreatment(['name' => "Tratamiento {$index}"])->id, range(1, 4));
    $doctors = array_map(fn (int $index): string => $this->createUserWithRole('doctor', ['first_name' => "Doctor{$index}", 'last_name' => 'Prueba'])->id, range(1, 3));

    $few = $this->createPatient();
    portalPerformanceAppointments($treatments, $doctors, $few->id, 5);
    $many = $this->createPatient();
    portalPerformanceAppointments($treatments, $doctors, $many->id, 100);

    $this->actingAs($few, 'patient');
    $this->getJson($url)->assertOk();
    $withFew = portalQueriesOf($this, $url);

    $this->actingAs($many, 'patient');
    $this->getJson($url)->assertOk();
    $withMany = portalQueriesOf($this, $url);

    expect(count($this->getJson('/api/v1/portal/appointments')->json('data')))->toBe(100)
        ->and($withMany)->toBe($withFew);
})->with([
    'mis citas' => '/api/v1/portal/appointments',
    'próxima cita' => '/api/v1/portal/appointments/next',
]);
