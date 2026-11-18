<?php

declare(strict_types=1);

use App\Modules\Patients\Domain\Repositories\PatientsRepositoryInterface;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 028 (CA1, CA4, CA5, CA9, CA10, CA13, CA14; P6, P7): GET /api/v1/patients answers one page of
 * twelve with its `meta`, and the counts of the whole list whatever the search and the filter.
 */
function patientsPaginationSeed(int $active, int $inactive = 0): void
{
    $hash = Hash::make('password');
    $start = Carbon::parse('2026-01-01 08:00:00');
    $rows = [];

    foreach (array_merge(array_fill(0, $active, 'active'), array_fill(0, $inactive, 'inactive')) as $index => $status) {
        $number = $index + 1;
        $rows[] = [
            'id' => (string) Str::uuid(),
            'first_name' => "Nombre{$number}",
            'last_name' => "Apellido{$number}",
            'email' => "paciente{$number}@example.com",
            'password' => $hash,
            'status' => $status,
            'role' => 'patient',
            'created_at' => $start->copy()->addMinutes($number),
            'updated_at' => $start->copy()->addMinutes($number),
        ];
    }

    PatientModel::query()->insert($rows);
}

it('answers the first page of twelve with its meta and the counts of the whole list', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(28, 2);

    $response = $this->getJson($this->patientsUrl());

    $response->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('data.0.email', 'paciente30@example.com')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
            'from' => 1,
            'to' => 12,
            'counts' => ['total' => 30, 'active' => 28, 'inactive' => 2],
        ]);
});

it('answers the page asked for', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(30);

    $this->getJson($this->patientsUrl().'?page=3')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('meta.page', 3)
        ->assertJsonPath('meta.from', 25)
        ->assertJsonPath('meta.to', 30);
});

it('answers the last page for a page beyond it', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(30);

    $this->getJson($this->patientsUrl().'?page=999')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('meta.page', 3);
});

it('never answers more than fifty patients at once', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(60);

    $this->getJson($this->patientsUrl().'?per_page=10000')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50)
        ->assertJsonPath('meta.last_page', 2);
});

it('keeps the counts of the whole list with a status filter or a search', function (string $query, int $total) {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(28, 2);

    $this->getJson($this->patientsUrl().$query)
        ->assertOk()
        ->assertJsonPath('meta.total', $total)
        ->assertJsonPath('meta.counts', ['total' => 30, 'active' => 28, 'inactive' => 2]);
})->with([
    'inactive' => ['?status=inactive', 2],
    'search' => ['?search=paciente5%40', 1],
    'both' => ['?status=inactive&search=nombre29', 1],
    'empty filters' => ['?status=&search=', 30],
]);

it('finds a patient of the last page from the first page by searching', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(30);

    $this->getJson($this->patientsUrl().'?page=1&search='.urlencode('Nombre3 Apellido3'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'paciente3@example.com')
        ->assertJsonPath('meta.total', 1);
});

it('answers an empty page without from nor to when nothing matches', function () {
    $this->actingAsNonAdminUser('Asistente');
    patientsPaginationSeed(3);

    $this->getJson($this->patientsUrl().'?search=%25')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null);
});

it('answers the page before when the only patient of the last page is deleted', function () {
    patientsPaginationSeed(13);
    $this->actingAsAdmin();
    $onLastPage = $this->getJson($this->patientsUrl().'?page=2')->json('data.0.id');

    $this->deleteJson($this->patientUrl($onLastPage))->assertOk();

    $this->getJson($this->patientsUrl().'?page=2')
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.page', 1)
        ->assertJsonPath('meta.last_page', 1);
});

it('refuses a patient with 403 and without meta', function () {
    patientsPaginationSeed(3);
    $this->actingAsPatient();

    $response = $this->getJson($this->patientsUrl());

    $response->assertForbidden()->assertJsonMissingPath('meta')->assertJsonMissingPath('data');
});

it('refuses an inactive staff member with 403 and without meta', function () {
    patientsPaginationSeed(3);
    $this->actingAsInactiveAdmin();

    $this->getJson($this->patientsUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
});

it('rejects invalid page parameters with 422', function (string $query, string $field) {
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson($this->patientsUrl().$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'page zero' => ['?page=0', 'page'],
    'page negative' => ['?page=-1', 'page'],
    'page array' => ['?page[]=1', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
    'per_page decimal' => ['?per_page=1.5', 'per_page'],
    'status unknown' => ['?status=x', 'status'],
    'search array' => ['?search[]=x', 'search'],
    'search too long' => ['?search='.str_repeat('a', 101), 'search'],
]);

it('answers the 422 messages in Spanish', function () {
    $this->actingAsNonAdminUser('Asistente');

    $this->getJson($this->patientsUrl().'?page=abc&status=x')
        ->assertUnprocessable()
        ->assertJsonPath('errors.page.0', 'La página debe ser un número entero mayor que cero.')
        ->assertJsonPath('errors.status.0', 'El estado debe ser active o inactive.');
});

it('answers a generic 500 logged once when reading the page fails', function () {
    $logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $logged->push($entry));
    $this->actingAsNonAdminUser('Asistente');
    $this->mock(PatientsRepositoryInterface::class)
        ->shouldReceive('findPage')->andThrow(new RuntimeException('paciente5@example.com'));

    $this->getJson($this->patientsUrl().'?search=paciente5')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);

    $unexpected = $logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error');

    expect($unexpected)->toHaveCount(1)
        ->and($logged->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))->implode("\n"))
        ->not->toContain('paciente5');
});

it('keeps the agenda patients list whole and without meta', function () {
    $this->actingAsAdmin();
    patientsPaginationSeed(30);

    $this->getJson('/api/v1/agenda/patients')
        ->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonMissingPath('meta');
});
