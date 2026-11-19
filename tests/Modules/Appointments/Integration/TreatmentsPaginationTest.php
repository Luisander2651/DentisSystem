<?php

declare(strict_types=1);

use App\Modules\Appointments\Domain\Repositories\TreatmentsRepositoryInterface;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\Modules\Appointments\Integration\AppointmentsIntegrationTestCase;

uses(AppointmentsIntegrationTestCase::class);

/**
 * Spec 028 (CA1, CA4, CA5, CA9, CA13, CA14; P6, P7): GET /api/v1/treatments answers one page of
 * twelve by name, with its `meta` and the stats of the whole catalogue whatever the search.
 */
beforeEach(function () {
    $this->seedTreatments = function (int $count): void {
        for ($i = 1; $i <= $count; $i++) {
            $this->createTreatment([
                'name' => sprintf('Tratamiento %02d', $i),
                'description' => "Descripción {$i}",
                'time' => $i === 1 ? null : 30,
            ]);
        }
    };
});

it('answers the first page of twelve with its meta and the stats of the catalogue', function () {
    $this->actingAsAdmin();
    ($this->seedTreatments)(30);

    $this->getJson($this->treatmentsAdminUrl())
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('data.0.name', 'Tratamiento 01')
        ->assertJsonPath('meta', [
            'page' => 1,
            'per_page' => 12,
            'total' => 30,
            'last_page' => 3,
            'from' => 1,
            'to' => 12,
            'counts' => ['total' => 30, 'with_duration' => 29, 'average_minutes' => 30],
        ]);
});

it('answers the page asked for and the last one for a page beyond it', function (string $query, int $page, int $count) {
    $this->actingAsAdmin();
    ($this->seedTreatments)(30);

    $this->getJson($this->treatmentsAdminUrl().$query)
        ->assertOk()
        ->assertJsonCount($count, 'data')
        ->assertJsonPath('meta.page', $page);
})->with([
    'third page' => ['?page=3', 3, 6],
    'beyond the last' => ['?page=999', 3, 6],
]);

it('never answers more than fifty treatments at once', function () {
    $this->actingAsAdmin();
    ($this->seedTreatments)(60);

    $this->getJson($this->treatmentsAdminUrl().'?per_page=10000')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50);
});

it('finds a treatment of the last page from the first page and keeps the stats of the catalogue', function () {
    $this->actingAsAdmin();
    ($this->seedTreatments)(30);

    $this->getJson($this->treatmentsAdminUrl().'?page=1&search='.urlencode('descripcion 29'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Tratamiento 29')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.counts', ['total' => 30, 'with_duration' => 29, 'average_minutes' => 30]);
});

it('answers no average when no treatment has a duration', function () {
    $this->actingAsAdmin();

    $this->getJson($this->treatmentsAdminUrl())
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.counts', ['total' => 0, 'with_duration' => 0, 'average_minutes' => null])
        ->assertJsonPath('meta.from', null);
});

it('refuses with 403 and without meta whoever is not an administrator', function (string $actor) {
    ($this->seedTreatments)(3);

    match ($actor) {
        'doctor' => $this->actingAsNonAdminUser('Doctor'),
        'asistente' => $this->actingAsNonAdminUser('Asistente'),
        'paciente' => $this->actingAsPatient(),
        'inactivo' => $this->actingAsInactiveAdmin(),
    };

    $this->getJson($this->treatmentsAdminUrl())
        ->assertForbidden()
        ->assertJsonMissingPath('meta')
        ->assertJsonMissingPath('data');
})->with(['doctor', 'asistente', 'paciente', 'inactivo']);

it('rejects invalid page parameters with 422', function (string $query, string $field) {
    $this->actingAsAdmin();

    $this->getJson($this->treatmentsAdminUrl().$query)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'page text' => ['?page=abc', 'page'],
    'page zero' => ['?page=0', 'page'],
    'per_page zero' => ['?per_page=0', 'per_page'],
    'per_page decimal' => ['?per_page=1.5', 'per_page'],
    'search array' => ['?search[]=x', 'search'],
    'search too long' => ['?search='.str_repeat('a', 101), 'search'],
]);

it('answers a generic 500 logged once when reading the page fails', function () {
    $logged = new Collection;
    Event::listen(MessageLogged::class, fn (MessageLogged $entry) => $logged->push($entry));
    $this->actingAsAdmin();
    $this->mock(TreatmentsRepositoryInterface::class)
        ->shouldReceive('findPage')->andThrow(new RuntimeException('detalle interno'));

    $this->getJson($this->treatmentsAdminUrl().'?search=limpieza')
        ->assertStatus(500)
        ->assertExactJson(['error' => 'Internal server error']);

    expect($logged->filter(fn (MessageLogged $entry): bool => $entry->message === 'unexpected_error'))->toHaveCount(1)
        ->and($logged->map(fn (MessageLogged $entry): string => $entry->message.' '.json_encode($entry->context))->implode("\n"))
        ->not->toContain('detalle interno');
});

it('keeps the agenda treatments whole and without meta', function () {
    $this->actingAsAdmin();
    ($this->seedTreatments)(15);

    $this->getJson('/api/v1/agenda/treatments')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonMissingPath('meta');
});
