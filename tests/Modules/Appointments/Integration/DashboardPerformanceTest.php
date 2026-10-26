<?php

declare(strict_types=1);

use App\Modules\Appointments\Infrastructure\Persistence\Eloquent\Models\AppointmentModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/*
 * Spec 020 (RNF, D7, D24, TM8, A12, A28): the dashboard is shown in less than 2 s with 50
 * appointments today, 200 unclosed and 2 000 in the month. Without the browser, that is the
 * server time of the four reads it asks for, one after another and warm (a first round is
 * discarded). Since the time depends on the machine, each read also makes a fixed number of
 * queries, the same with few appointments as with many: no N+1, nothing that grows with the data.
 */

const DASHBOARD_PERFORMANCE_READS = [
    'hoy' => '/api/v1/agenda/today-summary',
    'sin cerrar' => '/api/v1/agenda/unclosed',
    'sin seguimiento' => '/api/v1/agenda/without-tracking',
    'mes' => '/api/v1/reports/monthly?month=2026-10',
];

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-22 11:40:00', 'America/Mexico_City'));
    $this->actingAsAdmin();

    $this->treatments = array_map(fn (int $index): int => $this->createTreatment(['name' => "Tratamiento {$index}"])->id, range(1, 8));
    $this->doctors = array_map(fn (int $index): string => $this->createUserWithRole('doctor', ['first_name' => "Doctor{$index}", 'last_name' => 'Prueba'])->id, range(1, 6));
    $this->patients = array_map(fn (): string => $this->createPatient()->id, range(1, 60));
});

/**
 * Inserts appointments in bulk: `$today` of today in every status, `$unclosed` of earlier days
 * still assigned or rescheduled, and `$closed` of the month completed (without follow-up) or
 * cancelled, from the 1st to the 21st.
 */
function performanceAppointments(object $test, int $today, int $unclosed, int $closed): void
{
    $rows = [];
    $row = fn (int $index, string $date, string $time, string $status): array => [
        'id' => (string) Str::uuid(),
        'date' => $date,
        'time' => $time,
        'whatsapp_reminder' => false,
        'status' => $status,
        'treatment_id' => $test->treatments[$index % count($test->treatments)],
        'user_id' => $test->doctors[$index % count($test->doctors)],
        'patient_id' => $test->patients[$index % count($test->patients)],
        'created_at' => '2026-09-01 10:00:00',
        'updated_at' => '2026-09-01 10:00:00',
    ];

    foreach (range(0, $today - 1) as $index) {
        $rows[] = $row($index, '2026-10-22', sprintf('%02d:%02d', 8 + intdiv($index, 4), $index % 4 * 15), ['completada', 'asignada', 'reprogramada', 'cancelada'][$index % 4]);
    }

    foreach ($unclosed > 0 ? range(0, $unclosed - 1) : [] as $index) {
        $rows[] = $row($index, sprintf('2026-10-%02d', $index % 21 + 1), sprintf('%02d:%02d', 8 + $index % 11, $index % 2 * 30), $index % 3 === 0 ? 'reprogramada' : 'asignada');
    }

    foreach ($closed > 0 ? range(0, $closed - 1) : [] as $index) {
        $rows[] = $row($index, sprintf('2026-10-%02d', $index % 21 + 1), sprintf('%02d:%02d', 8 + $index % 11, $index % 4 * 15), $index % 5 === 0 ? 'cancelada' : 'completada');
    }

    foreach (array_chunk($rows, 500) as $chunk) {
        AppointmentModel::query()->insert($chunk);
    }
}

function performanceQueriesOf(object $test, string $url): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $test->getJson($url)->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $queries;
}

it('serves the four reads of the dashboard in less than 2 s with 50 today, 200 unclosed and 2 000 in the month (RNF)', function () {
    performanceAppointments($this, today: 50, unclosed: 200, closed: 1750);

    expect(AppointmentModel::query()->whereBetween('date', ['2026-10-01', '2026-10-31'])->count())->toBe(2000);

    foreach (DASHBOARD_PERFORMANCE_READS as $url) {
        $this->getJson($url)->assertOk();
    }

    $times = [];

    foreach (DASHBOARD_PERFORMANCE_READS as $name => $url) {
        $started = hrtime(true);
        $this->getJson($url)->assertOk();
        $times[$name] = (hrtime(true) - $started) / 1e9;
    }

    expect(array_sum($times))->toBeLessThan(2.0, 'the dashboard took '.json_encode($times));
});

it('makes the same number of queries with few appointments as with many (D24)', function (string $url) {
    performanceAppointments($this, today: 4, unclosed: 10, closed: 5);
    $this->getJson($url)->assertOk();
    $few = performanceQueriesOf($this, $url);

    performanceAppointments($this, today: 46, unclosed: 190, closed: 1745);
    $many = performanceQueriesOf($this, $url);

    expect($many)->toBe($few);
})->with([
    'hoy' => DASHBOARD_PERFORMANCE_READS['hoy'],
    'sin cerrar' => DASHBOARD_PERFORMANCE_READS['sin cerrar'],
]);

it('makes the same number of queries for the ones without follow-up with few appointments as with many (D24)', function () {
    $url = DASHBOARD_PERFORMANCE_READS['sin seguimiento'];
    performanceAppointments($this, today: 4, unclosed: 10, closed: 5);
    $this->getJson($url)->assertOk();
    $few = performanceQueriesOf($this, $url);

    performanceAppointments($this, today: 46, unclosed: 190, closed: 1745);
    $many = performanceQueriesOf($this, $url);

    expect($many)->toBe($few);
});

it('makes the same number of queries for the month with few appointments as with many (D24)', function () {
    $url = DASHBOARD_PERFORMANCE_READS['mes'];
    performanceAppointments($this, today: 4, unclosed: 10, closed: 5);
    $this->getJson($url)->assertOk();
    $few = performanceQueriesOf($this, $url);

    performanceAppointments($this, today: 46, unclosed: 190, closed: 1745);
    $many = performanceQueriesOf($this, $url);

    expect($many)->toBe($few);
});
