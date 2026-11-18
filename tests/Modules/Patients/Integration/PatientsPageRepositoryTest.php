<?php

declare(strict_types=1);

use App\Core\Domain\Pagination\PageRequest;
use App\Modules\Patients\Domain\Entities\Patient;
use App\Modules\Patients\Domain\Service\PatientService;
use App\Modules\Patients\Domain\ValueObjects\Patients\PatientStatus;
use App\Modules\Patients\Infrastructure\Persistence\Eloquent\Models\PatientModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Modules\Patients\Integration\PatientsIntegrationTestCase;

uses(PatientsIntegrationTestCase::class);

/**
 * Spec 028 (CA4, CA7, CA9; TM3): the patients list is read one page at a time, newest first, with
 * a literal search over the name and the email.
 */
function patientsPageHash(): string
{
    static $hash = null;

    return $hash ??= Hash::make('password');
}

function patientsPageSeed(int $count, string $status = 'active'): void
{
    for ($i = 1; $i <= $count; $i++) {
        patientsPageCreate([
            'first_name' => "Nombre{$i}",
            'last_name' => "Apellido{$i}",
            'email' => "paciente{$i}.{$status}@example.com",
            'status' => $status,
        ], Carbon::parse('2026-01-01 08:00:00')->addMinutes($i));
    }
}

/**
 * @param  array<string, string>  $overrides
 */
function patientsPageCreate(array $overrides = [], ?Carbon $createdAt = null): PatientModel
{
    $patient = PatientModel::create(array_merge([
        'id' => (string) Str::uuid(),
        'first_name' => 'Test',
        'last_name' => 'Patient',
        'email' => Str::uuid().'@example.com',
        'password' => patientsPageHash(),
        'status' => 'active',
        'role' => 'patient',
    ], $overrides));

    if ($createdAt !== null) {
        $patient->forceFill(['created_at' => $createdAt])->save();
    }

    return $patient;
}

function patientsPageService(): PatientService
{
    return app(PatientService::class);
}

/**
 * @param  list<Patient>  $patients
 * @return list<string>
 */
function patientsPageIds(array $patients): array
{
    return array_map(fn (Patient $patient): string => $patient->Id()->value, $patients);
}

it('reads the third page of thirty patients with the six left', function () {
    patientsPageSeed(30);

    $page = patientsPageService()->findPage(PageRequest::create(3, 12), null, null);

    expect($page->items)->toHaveCount(6)
        ->and($page->total)->toBe(30)
        ->and($page->page)->toBe(3)
        ->and($page->lastPage)->toBe(3)
        ->and($page->from)->toBe(25)
        ->and($page->to)->toBe(30);
});

it('lists the newest patients first', function () {
    patientsPageSeed(3);

    $page = patientsPageService()->findPage(PageRequest::create(1, 12), null, null);

    expect(array_map(fn (Patient $patient): string => $patient->Name()->firstName, $page->items))
        ->toBe(['Nombre3', 'Nombre2', 'Nombre1']);
});

it('gives the last page when the page asked for goes beyond it', function () {
    patientsPageSeed(30);

    $page = patientsPageService()->findPage(PageRequest::create(999, 12), null, null);

    expect($page->page)->toBe(3)
        ->and($page->items)->toHaveCount(6);
});

it('filters the page by status', function () {
    patientsPageSeed(4, 'active');
    patientsPageSeed(2, 'inactive');

    $page = patientsPageService()->findPage(PageRequest::create(1, 12), PatientStatus::inactive(), null);

    expect($page->total)->toBe(2)
        ->and(array_map(fn (Patient $patient): string => $patient->Status()->value, $page->items))
        ->toBe(['inactive', 'inactive']);
});

it('finds a patient of the third page from the first page by searching', function (string $search) {
    patientsPageSeed(30);

    $page = patientsPageService()->findPage(PageRequest::create(1, 12), null, $search);

    expect(array_map(fn (Patient $patient): string => $patient->Email()->value, $page->items))
        ->toBe(['paciente5.active@example.com']);
})->with([
    'first name' => '  nombre5 ',
    'last name' => 'APELLIDO5',
    'full name' => 'Nombre5 Apellido5',
    'email' => 'paciente5.active',
]);

it('searches the wildcards and quotes as literal text', function (string $search) {
    patientsPageSeed(5);

    $page = patientsPageService()->findPage(PageRequest::create(1, 12), null, $search);

    expect($page->total)->toBe(0)
        ->and($page->items)->toBe([]);
})->with([
    'percent' => '%',
    'underscore' => '_',
    'sql' => "' OR 1=1",
    'backslash' => '\\',
]);

it('finds the text that holds a wildcard when it is really there', function () {
    patientsPageSeed(3);
    patientsPageCreate(['first_name' => 'Ana_Luz', 'email' => 'ana_luz@example.com']);
    patientsPageCreate(['first_name' => 'AnaXLuz', 'email' => 'anaxluz@example.com']);

    $page = patientsPageService()->findPage(PageRequest::create(1, 12), null, 'ana_l');

    expect(array_map(fn (Patient $patient): string => $patient->Email()->value, $page->items))
        ->toBe(['ana_luz@example.com']);
});

it('gives every patient exactly once across the pages when they share the creation time', function () {
    $sameTime = Carbon::parse('2026-01-01 08:00:00');

    for ($i = 1; $i <= 25; $i++) {
        patientsPageCreate([], $sameTime);
    }

    $seen = [];

    foreach ([1, 2, 3] as $number) {
        $seen = [...$seen, ...patientsPageIds(patientsPageService()->findPage(PageRequest::create($number, 12), null, null)->items)];
    }

    expect($seen)->toHaveCount(25)
        ->and(array_unique($seen))->toHaveCount(25)
        ->and($seen)->toBe(PatientModel::query()->pluck('id')->sortDesc()->values()->all());
});

it('counts the whole list by status', function () {
    patientsPageSeed(4, 'active');
    patientsPageSeed(2, 'inactive');

    expect(patientsPageService()->countByStatus())->toBe(['total' => 6, 'active' => 4, 'inactive' => 2]);
});

it('counts zero for an empty list', function () {
    expect(patientsPageService()->countByStatus())->toBe(['total' => 0, 'active' => 0, 'inactive' => 0]);
});
