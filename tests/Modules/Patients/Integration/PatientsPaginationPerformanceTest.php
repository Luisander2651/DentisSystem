<?php

declare(strict_types=1);

use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/*
 * Spec 028 (CA12, A5): with 5 000 patients only the page reaches the browser. The time depends on
 * the machine (measured by hand in T009), so here the answer carries at most twelve patients, the
 * number of queries is the same with 5 as with 5 000, and the page is read with LIMIT and OFFSET:
 * nothing grows with the data.
 */

function patientsPerformanceInsert(int $count, int $offset = 0): void
{
    $hash = Hash::make('password');
    $start = Carbon::parse('2026-01-01 08:00:00');

    foreach (array_chunk(range($offset + 1, $offset + $count), 1000) as $numbers) {
        PatientModel::query()->insert(array_map(fn (int $number): array => [
            'id' => (string) Str::uuid(),
            'first_name' => "Nombre{$number}",
            'last_name' => "Apellido{$number}",
            'email' => "rendimiento{$number}@example.com",
            'password' => $hash,
            'status' => $number % 10 === 0 ? 'inactive' : 'active',
            'role' => 'patient',
            'created_at' => $start->copy()->addSeconds($number),
            'updated_at' => $start->copy()->addSeconds($number),
        ], $numbers));
    }
}

/**
 * @return list<array{query: string, bindings: array<int, mixed>, time: float|null}>
 */
function patientsPerformanceQueriesOf(object $test, string $url): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $test->getJson($url)->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    return $queries;
}

function patientsPerformancePageQuery(array $queries): string
{
    $pageQueries = array_values(array_filter(
        array_column($queries, 'query'),
        fn (string $sql): bool => str_contains($sql, 'from "patients"') && str_contains($sql, 'order by'),
    ));

    expect($pageQueries)->toHaveCount(1);

    return $pageQueries[0];
}

it('makes the same number of queries with 5 patients as with 5 000 and reads only the page', function () {
    $this->actingAsNonAdminUser('Asistente');
    $url = $this->patientsUrl().'?page=2&search=nombre';

    patientsPerformanceInsert(5);
    $this->getJson($url)->assertOk();
    $withFew = patientsPerformanceQueriesOf($this, $url);

    patientsPerformanceInsert(4995, 5);
    $withMany = patientsPerformanceQueriesOf($this, $url);
    $response = $this->getJson($this->patientsUrl());

    expect(count($withMany))->toBe(count($withFew))
        ->and(patientsPerformancePageQuery($withMany))->toContain('limit 12')->toContain('offset 12')
        ->and($response->json('data'))->toHaveCount(12)
        ->and($response->json('meta.total'))->toBe(5000)
        ->and($response->json('meta.counts'))->toBe(['total' => 5000, 'active' => 4500, 'inactive' => 500]);
});

it('reads a page far beyond the last one from the offset of the last page', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPerformanceInsert(5000);

    $queries = patientsPerformanceQueriesOf($this, $this->patientsUrl().'?page=999999999');
    $response = $this->getJson($this->patientsUrl().'?page=999999999');

    expect(patientsPerformancePageQuery($queries))->toContain('limit 12')->toContain('offset 4992')
        ->and($response->json('data'))->toHaveCount(8)
        ->and($response->json('meta.page'))->toBe(417);
});
